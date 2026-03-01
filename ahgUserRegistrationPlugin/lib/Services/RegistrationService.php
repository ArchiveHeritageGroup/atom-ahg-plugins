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

        // Hash password using AtoM's dual-layer approach
        $salt = md5(rand(100000, 999999) . $data['email']);
        $sha1Hash = sha1($salt . $data['password']);
        $hashAlgo = defined('PASSWORD_ARGON2I') ? PASSWORD_ARGON2I : PASSWORD_DEFAULT;
        $passwordHash = password_hash($sha1Hash, $hashAlgo);

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
     * Approve a registration request — creates user (inactive=0 → active=1 on approval).
     *
     * @return array{success: bool, error?: string, user_id?: int}
     */
    public function approve(int $requestId, int $adminId, ?string $notes = null, ?int $groupId = null): array
    {
        $request = DB::table($this->table)->where('id', $requestId)->first();

        if (!$request) {
            return ['success' => false, 'error' => 'Registration request not found.'];
        }

        if ($request->status !== 'verified') {
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

                // Step 4: Insert user record — ACTIVE (user is approved)
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

                // Step 6: Assign 'authenticated' group (99) — always required
                DB::table('acl_user_group')->insert([
                    'user_id' => $id,
                    'group_id' => 99,
                ]);

                // Step 7: Assign additional group if specified (default: contributor = 102)
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

            // Send approval email
            $this->sendApprovalEmail($request->email, $request->full_name);

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
            'registration_default_group', '102'
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

            $subject = "Verify your email — {$siteName}";
            $body = "Dear {$name},\n\n";
            $body .= "Thank you for registering at {$siteName}.\n\n";
            $body .= "Please click the link below to verify your email address:\n\n";
            $body .= "{$verifyUrl}\n\n";
            $body .= "This link expires in 48 hours.\n\n";
            $body .= "If you did not register, you can safely ignore this email.\n\n";
            $body .= "Regards,\n{$siteName}";

            $mailer = \sfContext::getInstance()->getMailer();
            $message = $mailer->compose(null, $email, $subject, $body);
            $mailer->send($message);
        } catch (\Exception $e) {
            // Email failure is non-fatal — admin can still see request
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

            $subject = "New registration request — {$siteName}";
            $body = "A new registration request has been submitted and email verified.\n\n";
            $body .= "Name: {$request->full_name}\n";
            $body .= "Email: {$request->email}\n";
            $body .= "Username: {$request->username}\n";
            $body .= "Institution: " . ($request->institution ?: 'Not specified') . "\n";
            $body .= "Research Interest: " . ($request->research_interest ?: 'Not specified') . "\n\n";
            $body .= "Review this request at:\n{$siteUrl}/admin/registrations\n\n";
            $body .= "Regards,\n{$siteName}";

            $mailer = \sfContext::getInstance()->getMailer();

            foreach ($admins as $admin) {
                try {
                    $message = $mailer->compose(null, $admin->email, $subject, $body);
                    $mailer->send($message);
                } catch (\Exception $e) {
                    // Continue to next admin
                }
            }
        } catch (\Exception $e) {
            // Non-fatal
        }
    }

    /**
     * Send approval email to user.
     */
    private function sendApprovalEmail(string $email, string $name): void
    {
        try {
            $siteName = \sfConfig::get('app_siteTitle', 'AtoM');
            $siteUrl = \sfConfig::get('app_siteBaseUrl', '');

            $subject = "Registration approved — {$siteName}";
            $body = "Dear {$name},\n\n";
            $body .= "Your registration at {$siteName} has been approved.\n\n";
            $body .= "You can now log in at:\n{$siteUrl}/user/login\n\n";
            $body .= "Regards,\n{$siteName}";

            $mailer = \sfContext::getInstance()->getMailer();
            $message = $mailer->compose(null, $email, $subject, $body);
            $mailer->send($message);
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

            $subject = "Registration update — {$siteName}";
            $body = "Dear {$name},\n\n";
            $body .= "We regret to inform you that your registration at {$siteName} has not been approved.\n\n";
            if ($reason) {
                $body .= "Reason: {$reason}\n\n";
            }
            $body .= "If you believe this is an error, please contact the administrator.\n\n";
            $body .= "Regards,\n{$siteName}";

            $mailer = \sfContext::getInstance()->getMailer();
            $message = $mailer->compose(null, $email, $subject, $body);
            $mailer->send($message);
        } catch (\Exception $e) {
            // Non-fatal
        }
    }
}
