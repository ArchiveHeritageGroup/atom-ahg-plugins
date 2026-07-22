<?php

/**
 * SAHRA Permit Service - National Heritage Resources Act, 1999 (Act 25 of 1999).
 *
 * Workflow lifecycle:
 *   draft -> pending_supervisor -> supervisor_approved -> submitted_to_sahra
 *         -> sahra_issued / active -> expired / closed
 *   branches: supervisor_rejected, sahra_rejected, revoked
 *
 * @package    ahgSAHRAPlugin
 * @subpackage Services
 */

namespace AhgSAHRA\Services;

use Illuminate\Database\Capsule\Manager as DB;

class SahraPermitService
{
    public const SECTIONS = [
        's35_archaeology' => 'Section 35 - Archaeology',
        's35_palaeontology' => 'Section 35 - Palaeontology',
        's35_meteorite' => 'Section 35 - Meteorites',
        's32_export' => 'Section 32 - Heritage object export',
        's34_structures' => 'Section 34 - Structures (60+ years)',
        's36_burial' => 'Section 36 - Burial grounds & graves',
    ];

    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'pending_supervisor' => 'Awaiting supervisor endorsement',
        'supervisor_approved' => 'Endorsed - ready for SAHRA',
        'supervisor_rejected' => 'Returned by supervisor',
        'submitted_to_sahra' => 'Submitted to SAHRA',
        'sahra_issued' => 'Permit issued by SAHRA',
        'active' => 'Active',
        'sahra_rejected' => 'Declined by SAHRA',
        'expired' => 'Expired',
        'revoked' => 'Revoked',
        'closed' => 'Closed',
    ];

    // ---------------------------------------------------------------
    // Config
    // ---------------------------------------------------------------

    public function getConfig(string $key, $default = null)
    {
        $row = DB::table('sahra_config')->where('config_key', $key)->first();
        return $row ? $row->config_value : $default;
    }

    public function setConfig(string $key, $value): void
    {
        DB::table('sahra_config')->updateOrInsert(
            ['config_key' => $key],
            ['config_value' => $value]
        );
    }

    /** @return string[] issuing authorities (SAHRA + PHRAs) */
    public function getAuthorities(): array
    {
        $raw = (string) $this->getConfig('authorities', 'SAHRA');
        return array_values(array_filter(array_map('trim', explode('|', $raw))));
    }

    // ---------------------------------------------------------------
    // Feature gate (per-instance / per-jurisdiction)
    //
    // The plugin ships in the shared AtoM/Heratio codebase, so instances in
    // other jurisdictions (e.g. Australia) have the code but must not see the
    // SAHRA feature. It stays dormant until an admin switches it on here.
    // ---------------------------------------------------------------

    /** Nav links this feature owns: [parentMenuName, menuName, path, label]. */
    private const MENU_LINKS = [
        ['manage', 'sahraPermits', 'sahra/index', 'SAHRA permits'],
        ['quickLinks', 'sahraMyPermits', 'sahra/my-applications', 'Heritage permits'],
    ];

    public function isFeatureEnabled(): bool
    {
        return (string) $this->getConfig('sahra_enabled', '0') === '1';
    }

    /** Toggle the feature and add/remove its nav links to match. */
    public function setFeatureEnabled(bool $on): void
    {
        $this->setConfig('sahra_enabled', $on ? '1' : '0');
        if ($on) {
            $this->addMenuLinks();
        } else {
            $this->removeMenuLinks();
        }
    }

    /** Idempotent nested-set insert of this feature's nav links. */
    public function addMenuLinks(): void
    {
        foreach (self::MENU_LINKS as [$parentName, $name, $path, $label]) {
            if (DB::table('menu')->where('name', $name)->exists()) {
                continue;
            }
            $parent = DB::table('menu')->where('name', $parentName)->first();
            if (!$parent) {
                continue;
            }
            $r = (int) $parent->rgt;
            $now = date('Y-m-d H:i:s');
            DB::transaction(function () use ($parent, $r, $now, $name, $path, $label) {
                DB::update('UPDATE menu SET rgt = rgt + 2 WHERE rgt >= ?', [$r]);
                DB::update('UPDATE menu SET lft = lft + 2 WHERE lft >= ?', [$r]);
                $id = DB::table('menu')->insertGetId([
                    'parent_id' => (int) $parent->id,
                    'name' => $name,
                    'path' => $path,
                    'lft' => $r,
                    'rgt' => $r + 1,
                    'source_culture' => 'en',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('menu_i18n')->insert(['id' => $id, 'culture' => 'en', 'label' => $label]);
                $this->assertMenuIntegrity();
            });
        }
    }

    /** Remove this feature's nav links, closing the nested-set gap. */
    public function removeMenuLinks(): void
    {
        foreach (self::MENU_LINKS as [, $name]) {
            $node = DB::table('menu')->where('name', $name)->first();
            if (!$node) {
                continue;
            }
            $lft = (int) $node->lft;
            $rgt = (int) $node->rgt;
            $width = $rgt - $lft + 1;
            DB::transaction(function () use ($node, $lft, $rgt, $width) {
                DB::table('menu_i18n')->where('id', $node->id)->delete();
                DB::table('menu')->where('id', $node->id)->delete();
                DB::update('UPDATE menu SET lft = lft - ? WHERE lft > ?', [$width, $rgt]);
                DB::update('UPDATE menu SET rgt = rgt - ? WHERE rgt > ?', [$width, $rgt]);
                $this->assertMenuIntegrity();
            });
        }
    }

    private function assertMenuIntegrity(): void
    {
        $agg = DB::table('menu')->selectRaw('COUNT(*) n, MIN(lft) mn, MAX(rgt) mx')->first();
        $bad = (int) DB::table('menu')->whereRaw('rgt <= lft')->count();
        $expected = (int) (((int) $agg->mx - (int) $agg->mn + 1) / 2);
        if ((int) $agg->n !== $expected || $bad > 0) {
            throw new \RuntimeException('menu nested-set integrity check failed (n=' . $agg->n . ' expected=' . $expected . ' bad=' . $bad . ')');
        }
    }

    // ---------------------------------------------------------------
    // Dashboard
    // ---------------------------------------------------------------

    public function getDashboardStats(): array
    {
        return [
            'pending_supervisor' => DB::table('sahra_permit')->where('status', 'pending_supervisor')->count(),
            'ready_for_sahra' => DB::table('sahra_permit')->where('status', 'supervisor_approved')->count(),
            'with_sahra' => DB::table('sahra_permit')->where('status', 'submitted_to_sahra')->count(),
            'active' => DB::table('sahra_permit')->whereIn('status', ['sahra_issued', 'active'])->count(),
            'expiring_soon' => DB::table('sahra_permit')
                ->whereIn('status', ['sahra_issued', 'active'])
                ->whereNotNull('end_date')
                ->whereRaw('end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)', [(int) $this->getConfig('expiry_warning_days', 30)])
                ->count(),
            'overdue_reports' => DB::table('sahra_permit_report')
                ->where('status', 'pending')
                ->whereNotNull('due_date')
                ->whereRaw('due_date < CURDATE()')
                ->count(),
            'total' => DB::table('sahra_permit')->count(),
        ];
    }

    // ---------------------------------------------------------------
    // Application (researcher)
    // ---------------------------------------------------------------

    public function generateApplicationRef(): string
    {
        $prefix = (string) $this->getConfig('application_prefix', 'SAHRA-APP');
        $year = date('Y');
        $count = DB::table('sahra_permit')->whereYear('created_at', $year)->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $year, $count);
    }

    /**
     * Create an application. Defaults to pending_supervisor (submitted for
     * endorsement); pass status 'draft' to save without submitting.
     */
    public function createApplication(array $data): int
    {
        $status = $data['status'] ?? 'pending_supervisor';

        $id = DB::table('sahra_permit')->insertGetId([
            'application_ref' => $this->generateApplicationRef(),
            'nhra_section' => $data['nhra_section'] ?? 's35_archaeology',
            'issuing_authority' => $data['issuing_authority'] ?? $this->getConfig('default_authority', 'SAHRA'),
            'applicant_user_id' => (int) $data['applicant_user_id'],
            'applicant_name' => $data['applicant_name'] ?? null,
            'applicant_email' => $data['applicant_email'] ?? null,
            'institution' => $data['institution'] ?? null,
            'supervisor_user_id' => !empty($data['supervisor_user_id']) ? (int) $data['supervisor_user_id'] : null,
            'supervisor_name' => $data['supervisor_name'] ?? null,
            'project_title' => $data['project_title'],
            'project_description' => $data['project_description'] ?? null,
            'site_name' => $data['site_name'] ?? null,
            'site_location' => $data['site_location'] ?? null,
            'province' => $data['province'] ?? null,
            'linked_object_id' => !empty($data['linked_object_id']) ? (int) $data['linked_object_id'] : null,
            'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
            'end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
            'status' => $status,
            'created_by' => (int) $data['applicant_user_id'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logAction($id, $status === 'draft' ? 'created' : 'submitted', (int) $data['applicant_user_id'], null, $status, 'Application created');

        if ($status === 'pending_supervisor' && !empty($data['supervisor_user_id'])) {
            $p = $this->getPermit($id);
            if ($p) {
                $this->notify(
                    $this->userEmail((int) $data['supervisor_user_id']),
                    'SAHRA permit application awaiting your endorsement',
                    $this->emailBody($p, 'Application awaiting your endorsement',
                        'A researcher has submitted a SAHRA / NHRA heritage permit application and nominated you as supervising professor. Please review and endorse or return it.')
                );
            }
        }

        return $id;
    }

    // ---------------------------------------------------------------
    // Queries
    // ---------------------------------------------------------------

    public function getPermit(int $id)
    {
        return DB::table('sahra_permit as p')
            ->leftJoin('user as a', 'p.applicant_user_id', '=', 'a.id')
            ->leftJoin('user as s', 'p.supervisor_user_id', '=', 's.id')
            ->where('p.id', $id)
            ->select('p.*', 'a.username as applicant_username', 's.username as supervisor_username')
            ->first();
    }

    public function getApplications(array $filters = []): array
    {
        $q = DB::table('sahra_permit as p')
            ->leftJoin('user as a', 'p.applicant_user_id', '=', 'a.id')
            ->leftJoin('user as s', 'p.supervisor_user_id', '=', 's.id')
            ->select('p.*', 'a.username as applicant_username', 's.username as supervisor_username');

        if (!empty($filters['status'])) {
            $q->where('p.status', $filters['status']);
        }
        if (!empty($filters['applicant_user_id'])) {
            $q->where('p.applicant_user_id', (int) $filters['applicant_user_id']);
        }
        if (!empty($filters['supervisor_user_id'])) {
            $q->where('p.supervisor_user_id', (int) $filters['supervisor_user_id']);
        }
        if (!empty($filters['nhra_section'])) {
            $q->where('p.nhra_section', $filters['nhra_section']);
        }

        return $q->orderByDesc('p.created_at')->get()->all();
    }

    public function getMyApplications(int $userId): array
    {
        return $this->getApplications(['applicant_user_id' => $userId]);
    }

    /** Applications awaiting endorsement by this supervisor. */
    public function getPendingForSupervisor(int $supervisorUserId, bool $isAdmin = false): array
    {
        $q = DB::table('sahra_permit as p')
            ->leftJoin('user as a', 'p.applicant_user_id', '=', 'a.id')
            ->where('p.status', 'pending_supervisor')
            ->select('p.*', 'a.username as applicant_username');

        if (!$isAdmin) {
            $q->where('p.supervisor_user_id', $supervisorUserId);
        }

        return $q->orderBy('p.created_at')->get()->all();
    }

    /** Endorsed applications ready to be lodged with SAHRA. */
    public function getSahraQueue(): array
    {
        return $this->getApplications(['status' => 'supervisor_approved']);
    }

    /** Applications lodged with SAHRA, awaiting SAHRA's in-system decision. */
    public function getSubmittedQueue(): array
    {
        return $this->getApplications(['status' => 'submitted_to_sahra']);
    }

    // ---------------------------------------------------------------
    // SAHRA reviewers (officials who decide from their side)
    // ---------------------------------------------------------------

    public function isSahraReviewer(int $userId): bool
    {
        return DB::table('sahra_reviewer')
            ->where('user_id', $userId)
            ->where('active', 1)
            ->exists();
    }

    public function getReviewers(): array
    {
        return DB::table('sahra_reviewer as r')
            ->join('user as u', 'r.user_id', '=', 'u.id')
            ->where('r.active', 1)
            ->select('r.*', 'u.username', 'u.email')
            ->orderBy('u.username')
            ->get()
            ->all();
    }

    public function addReviewer(int $userId, string $authority, int $addedBy): void
    {
        DB::table('sahra_reviewer')->updateOrInsert(
            ['user_id' => $userId],
            ['authority' => $authority ?: 'SAHRA', 'active' => 1, 'added_by' => $addedBy]
        );
    }

    public function removeReviewer(int $userId): void
    {
        DB::table('sahra_reviewer')->where('user_id', $userId)->update(['active' => 0]);
    }

    // ---------------------------------------------------------------
    // Workflow transitions
    // ---------------------------------------------------------------

    /** Supervisor endorses -> supervisor_approved. */
    public function endorse(int $id, int $userId, ?string $notes = null): bool
    {
        $p = DB::table('sahra_permit')->where('id', $id)->first();
        if (!$p || $p->status !== 'pending_supervisor') {
            return false;
        }
        DB::table('sahra_permit')->where('id', $id)->update([
            'status' => 'supervisor_approved',
            'supervisor_decision_date' => date('Y-m-d H:i:s'),
            'supervisor_notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->logAction($id, 'endorsed', $userId, $p->status, 'supervisor_approved', $notes);
        $this->notify($this->applicantEmail($p), 'Your SAHRA permit application was endorsed',
            $this->emailBody($p, 'Application endorsed',
                'Your supervising professor has endorsed your application. It is now with the heritage coordinator to lodge with SAHRA.'));
        return true;
    }

    /** Supervisor rejects -> supervisor_rejected (returned to researcher). */
    public function reject(int $id, int $userId, ?string $notes = null): bool
    {
        $p = DB::table('sahra_permit')->where('id', $id)->first();
        if (!$p || $p->status !== 'pending_supervisor') {
            return false;
        }
        DB::table('sahra_permit')->where('id', $id)->update([
            'status' => 'supervisor_rejected',
            'supervisor_decision_date' => date('Y-m-d H:i:s'),
            'supervisor_notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->logAction($id, 'rejected', $userId, $p->status, 'supervisor_rejected', $notes);
        $this->notify($this->applicantEmail($p), 'Your SAHRA permit application was returned',
            $this->emailBody($p, 'Application returned by your supervisor',
                'Your supervising professor has returned your application' . ($notes ? ' with the note: "' . htmlspecialchars($notes) . '"' : '') . '. Please review and resubmit.'));
        return true;
    }

    /** Coordinator lodges the endorsed application with SAHRA. */
    public function submitToSahra(int $id, int $userId, ?string $reference = null, ?string $notes = null): bool
    {
        $p = DB::table('sahra_permit')->where('id', $id)->first();
        if (!$p || $p->status !== 'supervisor_approved') {
            return false;
        }
        DB::table('sahra_permit')->where('id', $id)->update([
            'status' => 'submitted_to_sahra',
            'sahra_submitted_date' => date('Y-m-d H:i:s'),
            'sahra_reference' => $reference,
            'sahra_notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->logAction($id, 'sent_to_sahra', $userId, $p->status, 'submitted_to_sahra', $reference ? "SAHRA ref: {$reference}" : $notes);
        $this->notifyReviewers($p, 'New SAHRA permit application to review',
            'New application lodged for SAHRA decision',
            'An endorsed heritage permit application has been lodged and awaits your decision (issue or decline).');
        $this->notify($this->applicantEmail($p), 'Your SAHRA permit application was lodged with SAHRA',
            $this->emailBody($p, 'Application lodged with SAHRA',
                'Your application has been submitted to ' . htmlspecialchars($p->issuing_authority) . ' for a decision.'));
        return true;
    }

    /**
     * Record SAHRA's outcome. $outcome = 'issued' or 'rejected'.
     * For 'issued', $data may include: sahra_permit_number, start_date,
     * end_date, conditions, sahra_reference.
     */
    public function recordDecision(int $id, int $userId, string $outcome, array $data = []): bool
    {
        $p = DB::table('sahra_permit')->where('id', $id)->first();
        if (!$p || $p->status !== 'submitted_to_sahra') {
            return false;
        }

        if ($outcome === 'issued') {
            $update = [
                'status' => 'active',
                'sahra_permit_number' => $data['sahra_permit_number'] ?? null,
                'sahra_decision_date' => date('Y-m-d H:i:s'),
                'conditions' => $data['conditions'] ?? $p->conditions,
                'sahra_notes' => $data['sahra_notes'] ?? $p->sahra_notes,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if (!empty($data['start_date'])) {
                $update['start_date'] = $data['start_date'];
            }
            if (!empty($data['end_date'])) {
                $update['end_date'] = $data['end_date'];
            } elseif (empty($p->end_date)) {
                $months = (int) $this->getConfig('permit_validity_months', 36);
                $update['end_date'] = date('Y-m-d', strtotime("+{$months} months"));
            }
            if (!empty($data['sahra_reference'])) {
                $update['sahra_reference'] = $data['sahra_reference'];
            }
            DB::table('sahra_permit')->where('id', $id)->update($update);
            $this->logAction($id, 'issued', $userId, $p->status, 'active', 'Permit ' . ($data['sahra_permit_number'] ?? '') . ' issued');
            $permitNo = $data['sahra_permit_number'] ?? '';
            $this->notify($this->applicantEmail($p), 'Your SAHRA heritage permit has been issued',
                $this->emailBody($p, 'Permit issued',
                    'Good news - ' . htmlspecialchars($p->issuing_authority) . ' has issued your heritage permit'
                    . ($permitNo ? ' (number <strong>' . htmlspecialchars($permitNo) . '</strong>)' : '')
                    . '. Please review the conditions and validity period.'));
            $this->notify($this->userEmail((int) $p->supervisor_user_id), 'SAHRA permit issued for your student',
                $this->emailBody($p, 'Permit issued',
                    'A heritage permit you endorsed has been issued by ' . htmlspecialchars($p->issuing_authority) . '.'));
            return true;
        }

        DB::table('sahra_permit')->where('id', $id)->update([
            'status' => 'sahra_rejected',
            'sahra_decision_date' => date('Y-m-d H:i:s'),
            'sahra_notes' => $data['sahra_notes'] ?? $p->sahra_notes,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->logAction($id, 'sahra_rejected', $userId, $p->status, 'sahra_rejected', $data['sahra_notes'] ?? null);
        $this->notify($this->applicantEmail($p), 'Decision on your SAHRA permit application',
            $this->emailBody($p, 'Application declined',
                htmlspecialchars($p->issuing_authority) . ' has declined this application'
                . (!empty($data['sahra_notes']) ? ': "' . htmlspecialchars($data['sahra_notes']) . '"' : '') . '.'));
        return true;
    }

    public function revoke(int $id, int $userId, ?string $notes = null): bool
    {
        $p = DB::table('sahra_permit')->where('id', $id)->first();
        if (!$p || !in_array($p->status, ['sahra_issued', 'active', 'submitted_to_sahra'], true)) {
            return false;
        }
        DB::table('sahra_permit')->where('id', $id)->update([
            'status' => 'revoked',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->logAction($id, 'revoked', $userId, $p->status, 'revoked', $notes);
        return true;
    }

    /** Applicant cancels their own draft/pending application. */
    public function cancel(int $id, int $userId): bool
    {
        $p = DB::table('sahra_permit')->where('id', $id)->first();
        if (!$p || (int) $p->applicant_user_id !== $userId) {
            return false;
        }
        if (!in_array($p->status, ['draft', 'pending_supervisor', 'supervisor_rejected'], true)) {
            return false;
        }
        DB::table('sahra_permit')->where('id', $id)->update([
            'status' => 'closed',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->logAction($id, 'closed', $userId, $p->status, 'closed', 'Cancelled by applicant');
        return true;
    }

    // ---------------------------------------------------------------
    // Reporting obligations
    // ---------------------------------------------------------------

    public function addReport(int $permitId, array $data, int $userId): int
    {
        $id = DB::table('sahra_permit_report')->insertGetId([
            'permit_id' => $permitId,
            'report_type' => $data['report_type'] ?? 'interim',
            'due_date' => !empty($data['due_date']) ? $data['due_date'] : null,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->logAction($permitId, 'updated', $userId, null, null, 'Report obligation added (' . ($data['report_type'] ?? 'interim') . ')');
        return $id;
    }

    public function submitReport(int $reportId, array $data, int $userId): bool
    {
        $r = DB::table('sahra_permit_report')->where('id', $reportId)->first();
        if (!$r) {
            return false;
        }
        DB::table('sahra_permit_report')->where('id', $reportId)->update([
            'status' => 'submitted',
            'submitted_date' => date('Y-m-d'),
            'document_ref' => $data['document_ref'] ?? null,
            'notes' => $data['notes'] ?? $r->notes,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->logAction((int) $r->permit_id, 'updated', $userId, null, null, 'Report submitted');
        return true;
    }

    public function getReports(int $permitId): array
    {
        return DB::table('sahra_permit_report')
            ->where('permit_id', $permitId)
            ->orderBy('due_date')
            ->get()
            ->all();
    }

    public function getOverdueReports(): array
    {
        return DB::table('sahra_permit_report as r')
            ->join('sahra_permit as p', 'r.permit_id', '=', 'p.id')
            ->where('r.status', 'pending')
            ->whereNotNull('r.due_date')
            ->whereRaw('r.due_date < CURDATE()')
            ->select('r.*', 'p.application_ref', 'p.project_title')
            ->orderBy('r.due_date')
            ->get()
            ->all();
    }

    // ---------------------------------------------------------------
    // Expiry (used by the cron task)
    // ---------------------------------------------------------------

    public function getExpiring(int $days): array
    {
        return DB::table('sahra_permit')
            ->whereIn('status', ['sahra_issued', 'active'])
            ->whereNotNull('end_date')
            ->whereRaw('end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)', [$days])
            ->orderBy('end_date')
            ->get()
            ->all();
    }

    public function expireOverdue(int $actorId = 0): int
    {
        $overdue = DB::table('sahra_permit')
            ->whereIn('status', ['sahra_issued', 'active'])
            ->whereNotNull('end_date')
            ->whereRaw('end_date < CURDATE()')
            ->get();

        foreach ($overdue as $p) {
            DB::table('sahra_permit')->where('id', $p->id)->update([
                'status' => 'expired',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $this->logAction((int) $p->id, 'expired', $actorId ?: null, $p->status, 'expired', 'Auto-expired past end date');
        }

        return count($overdue);
    }

    // ---------------------------------------------------------------
    // Email notifications (best-effort; never break the workflow)
    // ---------------------------------------------------------------

    public function emailEnabled(): bool
    {
        return (string) $this->getConfig('email_notifications', '1') !== '0';
    }

    private function siteTitle(): string
    {
        $culture = class_exists('\sfConfig') ? \sfConfig::get('sf_default_culture', 'en') : 'en';
        $t = DB::table('setting_i18n')->join('setting', 'setting_i18n.id', '=', 'setting.id')
            ->where('setting.name', 'siteTitle')->where('setting_i18n.culture', $culture)->value('setting_i18n.value');
        if (!$t) {
            $t = DB::table('setting_i18n')->join('setting', 'setting_i18n.id', '=', 'setting.id')
                ->where('setting.name', 'siteTitle')->value('setting_i18n.value');
        }
        return $t ?: 'Archive';
    }

    private function permitUrl(int $id): string
    {
        $base = class_exists('\sfConfig') ? rtrim((string) \sfConfig::get('app_siteBaseUrl', ''), '/') : '';
        return $base . '/index.php/sahra/permit/' . $id;
    }

    public function userEmail(?int $userId): ?string
    {
        if (!$userId) {
            return null;
        }
        return DB::table('user')->where('id', $userId)->value('email');
    }

    private function applicantEmail(object $p): ?string
    {
        return $p->applicant_email ?: $this->userEmail((int) $p->applicant_user_id);
    }

    private function notify(?string $to, string $subject, string $bodyHtml): void
    {
        if (!$to || !$this->emailEnabled()) {
            return;
        }
        try {
            $svcPath = (class_exists('\sfConfig') ? \sfConfig::get('sf_plugins_dir', '') : '') . '/ahgCorePlugin/lib/Services/EmailService.php';
            if (!class_exists('\AhgCore\Services\EmailService') && $svcPath && file_exists($svcPath)) {
                require_once $svcPath;
            }
            if (class_exists('\AhgCore\Services\EmailService') && \AhgCore\Services\EmailService::isEnabled()) {
                \AhgCore\Services\EmailService::send($to, $subject, $bodyHtml);
                return;
            }
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . $this->siteTitle() . ' <noreply@theahg.co.za>',
            ];
            @mail($to, $subject, $bodyHtml, implode("\r\n", $headers));
        } catch (\Throwable $e) {
            // best effort
        }
    }

    private function emailBody(object $p, string $heading, string $message): string
    {
        $site = htmlspecialchars($this->siteTitle());
        $ref = htmlspecialchars($p->application_ref);
        $title = htmlspecialchars($p->project_title);
        $url = $this->permitUrl((int) $p->id);
        return "<html><body style='font-family:Arial,sans-serif;'>"
            . "<h2>{$heading}</h2>"
            . "<p>{$message}</p>"
            . "<table style='border-collapse:collapse;'>"
            . "<tr><td style='padding:4px 12px 4px 0;'><strong>Reference</strong></td><td>{$ref}</td></tr>"
            . "<tr><td style='padding:4px 12px 4px 0;'><strong>Project</strong></td><td>{$title}</td></tr>"
            . "</table>"
            . "<p><a href='{$url}' style='background:#10373E;color:#fff;padding:8px 16px;text-decoration:none;'>View application</a></p>"
            . "<p style='color:#888;font-size:12px;'>{$site} - SAHRA / NHRA heritage permits</p>"
            . "</body></html>";
    }

    /** Notify all active SAHRA reviewers (used when an application is lodged). */
    private function notifyReviewers(object $p, string $subject, string $heading, string $message): void
    {
        foreach ($this->getReviewers() as $rev) {
            if (!empty($rev->email)) {
                $this->notify($rev->email, $subject, $this->emailBody($p, $heading, $message));
            }
        }
    }

    // ---------------------------------------------------------------
    // Log
    // ---------------------------------------------------------------

    public function getPermitLog(int $permitId): array
    {
        return DB::table('sahra_permit_log as l')
            ->leftJoin('user as u', 'l.actor_id', '=', 'u.id')
            ->where('l.permit_id', $permitId)
            ->select('l.*', 'u.username as actor_username')
            ->orderByDesc('l.created_at')
            ->get()
            ->all();
    }

    public function logAction(int $permitId, string $action, ?int $actorId, ?string $from = null, ?string $to = null, ?string $notes = null): void
    {
        try {
            DB::table('sahra_permit_log')->insert([
                'permit_id' => $permitId,
                'action' => $action,
                'actor_id' => $actorId,
                'from_status' => $from,
                'to_status' => $to,
                'notes' => $notes,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // logging must never break the workflow
        }
    }
}
