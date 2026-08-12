<?php

namespace AhgUserRegistration\Services;

use Illuminate\Database\Capsule\Manager as DB;
use AhgCore\Services\ObjectService;
use AhgCore\Services\I18nService;

class RegistrationService
{
    private string $table = 'ahg_registration_request';

    /**
     * Create a new registration request.
     *
     * @return array{success: bool, error?: string, request_id?: int}
     */
    public function createRequest(array $data, ?string $ipAddress = null): array
    {
        // Rate limiting: max 5 registrations per IP per hour
        if ($ipAddress && $this->isRateLimited($ipAddress)) {
            return ['success' => false, 'error' => 'Too many registration attempts. Please try again later.'];
        }

        // Check for existing email
        $existing = DB::table($this->table)
            ->where('email', $data['email'])
            ->whereIn('status', ['pending', 'verified'])
            ->first();

        if ($existing) {
            return ['success' => false, 'error' => 'A registration request with this email is already pending.'];
        }

        // Check if email already exists as a user
        $existingUser = DB::table('user')->where('email', $data['email'])->first();
        if ($existingUser) {
            return ['success' => false, 'error' => 'An account with this email already exists.'];
        }

        // Check if username already exists
        $existingUsername = DB::table('user')->where('username', $data['username'])->first();
        if ($existingUsername) {
            return ['success' => false, 'error' => 'This username is already taken.'];
        }

        // A hash stock AtoM can verify, stored on the request row and copied
        // verbatim to the user on approval.
        //
        // PasswordService::hash() writes Argon2id over the plaintext with an
        // empty salt. That is the better scheme and an AHG instance reads it -
        // but QubitUser::checkCredentials() in unmodified AtoM computes only
        //
        //     password_verify(sha1($user->getSalt() . $password), $hash)
        //
        // so the account created at approval could never log in. The applicant
        // registered, verified their address, waited for an administrator, and
        // was handed an account that rejects its own password. Nothing in the
        // flow reports it: the login screen just says the email or password is
        // unrecognised.
        //
        // Patching base AtoM is not the answer - a base patch reverts silently on
        // the next upgrade and takes every account created this way with it. So
        // write the shape AtoM verifies, but with random_bytes(16) rather than
        // AtoM's own md5(rand(100000, 999999) . email), which is six digits of
        // entropy keyed on a value the attacker already has.
        //
        // A non-empty salt is exactly what PasswordService::verify() routes to
        // its legacy branch, so an AHG instance reads these correctly too.
        $salt = bin2hex(random_bytes(16));
        $passwordHash = password_hash(sha1($salt.(string) $data['password']), PASSWORD_DEFAULT);

        // Generate email verification token
        $token = bin2hex(random_bytes(32));

        $requestId = DB::table($this->table)->insertGetId([
            'email' => $data['email'],
            'username' => $data['username'],
            'password_hash' => $passwordHash,
            'salt' => $salt,
            'full_name' => $data['full_name'],
            'institution' => $data['institution'] ?? null,
            'research_interest' => $data['research_interest'] ?? null,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
            'email_token' => $token,
            'ip_address' => $ipAddress,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Send verification email
        $this->sendVerificationEmail($data['email'], $data['full_name'], $token);

        return ['success' => true, 'request_id' => $requestId];
    }

    /**
     * Verify email via token.
     *
     * @return array{success: bool, error?: string}
     */
    public function verifyEmail(string $token): array
    {
        $request = DB::table($this->table)
            ->where('email_token', $token)
            ->first();

        if (!$request) {
            return ['success' => false, 'error' => 'Invalid or expired verification token.'];
        }

        if ($request->status !== 'pending') {
            return ['success' => false, 'error' => 'This registration has already been processed.'];
        }

        // Check token expiry (48 hours)
        $createdAt = strtotime($request->created_at);
        if (time() - $createdAt > 172800) {
            DB::table($this->table)->where('id', $request->id)->update(['status' => 'expired']);
            return ['success' => false, 'error' => 'Verification token has expired. Please register again.'];
        }

        DB::table($this->table)->where('id', $request->id)->update([
            'status' => 'verified',
            'email_verified_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Notify admins
        $this->notifyAdminsNewRegistration($request);

        return ['success' => true];
    }

    /**
     * Admin - manually mark a pending request's email as verified.
     * Used when the verification email could not be delivered (e.g. mail down)
     * and the applicant confirms their identity out-of-band (phone/in person).
     */
    public function markVerified(int $requestId): array
    {
        $request = DB::table($this->table)->where('id', $requestId)->first();

        if (!$request) {
            return ['success' => false, 'error' => 'Registration request not found.'];
        }

        // Already verified is the outcome the caller asked for. Same reasoning as
        // approve() above: a repeated click should not be reported as a failure.
        if ('verified' === $request->status) {
            return ['success' => true, 'already' => true];
        }

        if ('pending' !== $request->status) {
            return ['success' => false, 'error' => 'Only pending requests can be marked verified.'];
        }

        DB::table($this->table)->where('id', $requestId)->update([
            'status' => 'verified',
            'email_verified_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    /**
     * Get pending (verified) registrations for admin queue.
     */
    public function getPendingRegistrations(): array
    {
        return DB::table($this->table)
            ->where('status', 'verified')
            ->orderBy('created_at', 'asc')
            ->get()
            ->all();
    }

    /**
     * Get all registrations for admin view.
     */
    public function getAllRegistrations(?string $statusFilter = null): array
    {
        $query = DB::table($this->table)
            ->orderBy('created_at', 'desc');

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        return $query->get()->all();
    }

    /**
     * Approve a registration request - creates user (inactive=0 -> active=1 on approval).
     *
     * @return array{success: bool, error?: string, user_id?: int}
     */
    public function approve(int $requestId, int $adminId, ?string $notes = null, ?int $groupId = null): array
    {
        $request = DB::table($this->table)->where('id', $requestId)->first();

        if (!$request) {
            return ['success' => false, 'error' => 'Registration request not found.'];
        }

        // Answer for the state the request is actually in.
        //
        // This was one test - status !== 'verified' - reporting every other state
        // as an email-verification failure. The state that mattered was
        // 'approved': a second submission of an approval that had already
        // succeeded was told the applicant had not confirmed their email, which
        // is both untrue and alarming. It reads as "the approval failed" when the
        // account exists and is active, and it is what an administrator sees
        // whenever they click Approve twice - reported as "error but it approves".
        //
        // Already approved is reported as success. A second click means the
        // administrator could not tell the first worked; the outcome they wanted
        // holds, so saying so is both accurate and the useful answer. Nothing is
        // written twice - this returns before the transaction.
        if ('approved' === $request->status) {
            return [
                'success' => true,
                'already' => true,
                'user_id' => (int) DB::table('user')->where('username', $request->username)->value('id'),
            ];
        }

        if ('rejected' === $request->status) {
            return ['success' => false, 'error' => 'This registration was rejected and cannot be approved.'];
        }

        if ('expired' === $request->status) {
            return ['success' => false, 'error' => 'This registration expired before it was confirmed.'];
        }

        if ('verified' !== $request->status) {
            return ['success' => false, 'error' => 'Only email-verified registrations can be approved.'];
        }

        // Double-check email/username not taken since registration
        $existingUser = DB::table('user')->where('email', $request->email)->first();
        if ($existingUser) {
            return ['success' => false, 'error' => 'An account with this email was created since registration.'];
        }

        $existingUsername = DB::table('user')->where('username', $request->username)->first();
        if ($existingUsername) {
            return ['success' => false, 'error' => 'This username was taken since registration.'];
        }

        try {
            $userId = DB::transaction(function () use ($request, $adminId, $notes, $groupId) {
                $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();

                // Step 1: Create object
                $id = ObjectService::create('QubitUser');

                // Step 2: Create actor (user extends actor)
                DB::table('actor')->insert([
                    'id' => $id,
                    'parent_id' => \QubitActor::ROOT_ID,
                    'source_culture' => $culture,
                ]);

                // Step 3: Save actor i18n (display name)
                I18nService::save('actor_i18n', $id, $culture, [
                    'authorized_form_of_name' => $request->full_name,
                ]);

                // Step 4: Insert user record - ACTIVE (user is approved)
                DB::table('user')->insert([
                    'id' => $id,
                    'username' => $request->username,
                    'email' => $request->email,
                    'password_hash' => $request->password_hash,
                    'salt' => $request->salt,
                    'active' => 1,
                ]);

                // Step 5: Generate slug
                ObjectService::generateSlug($id, $request->username);

                // Step 6: no 'authenticated' row - group 99 is implicit.
                //
                // QubitUser::getAclGroups() prepends AUTHENTICATED_ID to every
                // user (lib/model/QubitUser.php:108-119). Writing it as well makes
                // the group appear twice, and Zend's ACL registry throws
                // "Role id '99' already exists in the registry" - a 500 on every
                // page for that account.
                //
                // Harmless here only because step 7 also assigns a real group, so
                // the duplicate sat alongside a distinct one. It is removed
                // because the row is redundant either way, and because a caller
                // that skips step 7 would inherit the fault.

                // Step 7: assign a group beyond plain authenticated, if one was chosen.
                //
                // The default is authenticated (99), not contributor (102).
                // Contributor carries edit rights, so approving a self-service
                // registration on the defaults handed every applicant the ability to
                // modify descriptions - an access grant nobody asked for and which
                // the approving administrator was never shown.
                //
                // Nothing is inserted for 99: QubitUser::getAclGroups() prepends it
                // to every authenticated user already, and an explicit row is the
                // duplicate-role fault removed above.
                $assignGroupId = $groupId ?: $this->getDefaultGroupId();
                if ($assignGroupId && $assignGroupId > 99) {
                    DB::table('acl_user_group')->insert([
                        'user_id' => $id,
                        'group_id' => $assignGroupId,
                    ]);
                }

                // Update registration request
                DB::table($this->table)->where('id', $request->id)->update([
                    'status' => 'approved',
                    'admin_notes' => $notes,
                    'reviewed_by' => $adminId,
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'assigned_group_id' => $assignGroupId,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                return $id;
            });

            // Send approval email, naming the access level that was assigned.
            $assigned = DB::table($this->table)->where('id', $requestId)->value('assigned_group_id');
            $this->sendApprovalEmail($request->email, $request->full_name, $assigned ? (int) $assigned : null);

            return ['success' => true, 'user_id' => $userId];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Error creating user: ' . $e->getMessage()];
        }
    }

    /**
     * Reject a registration request.
     *
     * @return array{success: bool, error?: string}
     */
    public function reject(int $requestId, int $adminId, ?string $notes = null): array
    {
        $request = DB::table($this->table)->where('id', $requestId)->first();

        if (!$request) {
            return ['success' => false, 'error' => 'Registration request not found.'];
        }

        if (!in_array($request->status, ['pending', 'verified'])) {
            return ['success' => false, 'error' => 'This registration has already been processed.'];
        }

        DB::table($this->table)->where('id', $requestId)->update([
            'status' => 'rejected',
            'admin_notes' => $notes,
            'reviewed_by' => $adminId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Send rejection email
        $this->sendRejectionEmail($request->email, $request->full_name, $notes);

        return ['success' => true];
    }

    /**
     * Clean up expired unverified requests (older than 48 hours).
     */
    public function cleanupExpired(): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - 172800);

        return DB::table($this->table)
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->update(['status' => 'expired', 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Get a single request by ID.
     */
    public function getRequest(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    /**
     * Check rate limiting.
     */
    private function isRateLimited(string $ipAddress): bool
    {
        $oneHourAgo = date('Y-m-d H:i:s', time() - 3600);

        $count = DB::table($this->table)
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>', $oneHourAgo)
            ->count();

        $maxPerHour = (int) \AtomExtensions\Services\AhgSettingsService::get(
            'registration_max_per_hour', '5'
        );

        return $count >= $maxPerHour;
    }

    /**
     * Get default group ID for new registrations.
     */
    private function getDefaultGroupId(): int
    {
        return (int) \AtomExtensions\Services\AhgSettingsService::get(
            'registration_default_group', '99'
        );
    }

    /**
     * Send verification email.
     */
    private function sendVerificationEmail(string $email, string $name, string $token): void
    {
        try {
            $siteUrl = \sfConfig::get('app_siteBaseUrl', '');
            $verifyUrl = $siteUrl . '/register/verify/' . $token;
            $siteName = \sfConfig::get('app_siteTitle', 'AtoM');

            // Says what happens next, and that verifying is not the last step.
            //
            // It used to stop at "verify your email", so an applicant verified,
            // went straight to the login form, and was told their email or
            // password was unrecognised - because no account exists until an
            // administrator approves. The message was accurate and still left
            // people stuck.
            $subject = "Verify your email - {$siteName}";
            $body = "Dear {$name},\n\n";
            $body .= "Thank you for registering at {$siteName}.\n\n";
            $body .= "Please click the link below to verify your email address:\n\n";
            $body .= "{$verifyUrl}\n\n";
            $body .= "This link expires in 48 hours.\n\n";
            $body .= "What happens after that:\n\n";
            $body .= "1. We receive your verified request.\n";
            $body .= "2. An administrator reviews it and decides your level of access.\n";
            $body .= "3. You receive a second email once your account is ready.\n\n";
            $body .= "You will not be able to sign in until that second email arrives. ";
            $body .= "If you try before then, the login page will not recognise your details, ";
            $body .= "because the account is only created at approval.\n\n";
            $body .= "If you did not register, you can safely ignore this email.\n\n";
            $body .= "Regards,\n{$siteName}";

            // Send via the settings-driven AHG mailer (email_setting -> SMTP/sendmail),
            // not AtoM's Swift mailer (which targets a dead localhost SMTP).
            \AhgCore\Services\EmailService::send($email, $subject, $body);
        } catch (\Exception $e) {
            // Email failure is non-fatal - admin can still see request
        }
    }

    /**
     * Notify admins about a new verified registration.
     */
    private function notifyAdminsNewRegistration(object $request): void
    {
        try {
            $siteName = \sfConfig::get('app_siteTitle', 'AtoM');
            $siteUrl = \sfConfig::get('app_siteBaseUrl', '');

            // Get admin emails
            $admins = DB::table('user')
                ->join('acl_user_group', 'user.id', '=', 'acl_user_group.user_id')
                ->where('acl_user_group.group_id', 100)
                ->where('user.active', 1)
                ->select('user.email')
                ->get();

            if ($admins->isEmpty()) {
                return;
            }

            $subject = "New registration request - {$siteName}";
            $body = "A new registration request has been submitted and email verified.\n\n";
            $body .= "Name: {$request->full_name}\n";
            $body .= "Email: {$request->email}\n";
            $body .= "Username: {$request->username}\n";
            $body .= "Institution: " . ($request->institution ?: 'Not specified') . "\n";
            $body .= "Research Interest: " . ($request->research_interest ?: 'Not specified') . "\n\n";
            $body .= "Review this request at:\n{$siteUrl}/admin/registrations\n\n";
            $body .= "Regards,\n{$siteName}";

            foreach ($admins as $admin) {
                // Settings-driven AHG mailer (email_setting), not AtoM's Swift mailer.
                \AhgCore\Services\EmailService::send($admin->email, $subject, $body);
            }
        } catch (\Exception $e) {
            // Non-fatal
        }
    }

    /**
     * Send approval email to user.
     */
    /**
     * Human-readable name for an ACL group, or null if it cannot be resolved.
     *
     * Read from acl_group_i18n rather than hardcoded, because the group names
     * are editable and an institution may well have renamed them.
     */
    private function groupName(int $groupId): ?string
    {
        try {
            $name = DB::table('acl_group_i18n')
                ->where('id', $groupId)
                ->where('culture', 'en')
                ->value('name');

            return $name ? (string) $name : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function sendApprovalEmail(string $email, string $name, ?int $groupId = null): void
    {
        try {
            $siteName = \sfConfig::get('app_siteTitle', 'AtoM');
            $siteUrl = \sfConfig::get('app_siteBaseUrl', '');

            // Name the access level that was granted.
            //
            // Approval assigns a group - administrator, editor, contributor or
            // translator - and that choice decides what the person can actually
            // do. The email said only "approved", so the one fact decided on
            // their behalf was the one fact not communicated.
            $roleLine = '';

            if (null !== $groupId) {
                $role = $this->groupName($groupId);

                if (null !== $role) {
                    $roleLine = "Your access level is: {$role}.\n\n";
                }
            }

            $subject = "Registration approved - {$siteName}";
            $body = "Dear {$name},\n\n";
            $body .= "Your registration at {$siteName} has been approved and your account is ready.\n\n";
            $body .= $roleLine;
            $body .= "You can now sign in at:\n{$siteUrl}/user/login\n\n";
            $body .= "Sign in with the email address and password you chose when you registered.\n\n";
            $body .= "If you need a different level of access, reply to this message and ask.\n\n";
            $body .= "Regards,\n{$siteName}";

            // Send via the settings-driven AHG mailer (email_setting -> SMTP/sendmail),
            // not AtoM's Swift mailer (which targets a dead localhost SMTP).
            \AhgCore\Services\EmailService::send($email, $subject, $body);
        } catch (\Exception $e) {
            // Non-fatal
        }
    }

    /**
     * Send rejection email to user.
     */
    private function sendRejectionEmail(string $email, string $name, ?string $reason = null): void
    {
        try {
            $siteName = \sfConfig::get('app_siteTitle', 'AtoM');

            $subject = "Registration update - {$siteName}";
            $body = "Dear {$name},\n\n";
            $body .= "We regret to inform you that your registration at {$siteName} has not been approved.\n\n";
            if ($reason) {
                $body .= "Reason: {$reason}\n\n";
            }
            $body .= "If you believe this is an error, please contact the administrator.\n\n";
            $body .= "Regards,\n{$siteName}";

            // Send via the settings-driven AHG mailer (email_setting -> SMTP/sendmail),
            // not AtoM's Swift mailer (which targets a dead localhost SMTP).
            \AhgCore\Services\EmailService::send($email, $subject, $body);
        } catch (\Exception $e) {
            // Non-fatal
        }
    }
}
