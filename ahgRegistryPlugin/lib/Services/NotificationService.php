<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * In-app + email notifications for the registry.
 *
 * Notifications are stored in `registry_notification` (one row per recipient)
 * and additionally emailed when SMTP is configured (registry_settings).
 */
class NotificationService
{
    protected string $culture;
    protected string $table = 'registry_notification';

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    // =========================================================================
    // Create
    // =========================================================================

    /**
     * Notify every active administrator user.
     * Returns the number of notifications inserted (one per admin).
     */
    public function notifyAdmins(
        string $type,
        string $title,
        string $message = '',
        ?string $link = null,
        ?string $relatedType = null,
        $relatedId = null,
        ?int $actorUserId = null,
        ?string $actorName = null
    ): int {
        $adminGroupId = defined('\\AtomExtensions\\Constants\\AclConstants::ADMINISTRATOR_ID')
            ? \AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID
            : 1;

        $admins = DB::table('user as u')
            ->join('acl_user_group as g', 'g.user_id', '=', 'u.id')
            ->where('g.group_id', $adminGroupId)
            ->where('u.active', 1)
            ->select('u.id', 'u.email')
            ->distinct()
            ->get()->all();

        if (empty($admins)) {
            return 0;
        }

        $count = 0;
        foreach ($admins as $admin) {
            // Don't notify the admin who triggered the event
            if ($actorUserId && (int) $admin->id === (int) $actorUserId) {
                continue;
            }

            $this->notify(
                (int) $admin->id,
                $type,
                $title,
                $message,
                $link,
                $relatedType,
                $relatedId,
                $actorUserId,
                $actorName
            );

            // Email out (best-effort)
            if (!empty($admin->email)) {
                try {
                    $this->sendEmail($admin->email, $title, $message, $link);
                } catch (\Throwable $e) {
                    error_log('Registry notification email failed for ' . $admin->email . ': ' . $e->getMessage());
                }
            }

            $count++;
        }

        return $count;
    }

    /**
     * Insert a single notification.
     */
    public function notify(
        int $userId,
        string $type,
        string $title,
        string $message = '',
        ?string $link = null,
        ?string $relatedType = null,
        $relatedId = null,
        ?int $actorUserId = null,
        ?string $actorName = null
    ): int {
        return (int) DB::table($this->table)->insertGetId([
            'user_id' => $userId,
            'type' => $type,
            'title' => mb_substr($title, 0, 255),
            'message' => $message,
            'link' => $link ? mb_substr($link, 0, 500) : null,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'actor_user_id' => $actorUserId,
            'actor_name' => $actorName ? mb_substr($actorName, 0, 255) : null,
            'is_read' => 0,
            'is_dismissed' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // =========================================================================
    // Read
    // =========================================================================

    public function unreadCount(int $userId): int
    {
        return (int) DB::table($this->table)
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
    }

    /** Recent notifications (for dropdown). */
    public function recent(int $userId, int $limit = 10): array
    {
        return DB::table($this->table)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()->all();
    }

    /** All notifications (for full-page list, paginated). */
    public function browse(int $userId, int $page = 1, int $perPage = 25): array
    {
        $query = DB::table($this->table)->where('user_id', $userId);
        $total = (clone $query)->count();
        $items = $query->orderBy('created_at', 'desc')
            ->offset(max(0, ($page - 1) * $perPage))
            ->limit($perPage)
            ->get()->all();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    /** Latest unread, un-dismissed item shown in the top bar (or null). */
    public function topBarItem(int $userId): ?object
    {
        $row = DB::table($this->table)
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->where('is_dismissed', 0)
            ->orderBy('created_at', 'desc')
            ->first();

        return $row ?: null;
    }

    // =========================================================================
    // Update
    // =========================================================================

    public function markRead(int $id, int $userId): bool
    {
        return (bool) DB::table($this->table)
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
    }

    public function markAllRead(int $userId): int
    {
        return (int) DB::table($this->table)
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
    }

    public function dismissBar(int $id, int $userId): bool
    {
        return (bool) DB::table($this->table)
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update(['is_dismissed' => 1]);
    }

    // =========================================================================
    // Email out
    // =========================================================================

    protected function sendEmail(string $to, string $subject, string $message, ?string $link): bool
    {
        $smtp = $this->getSmtpSettings();
        $baseUrl = \sfConfig::get('app_registry_base_url', '') ?: 'https://registry.theahg.co.za';
        $absLink = $link ? ((0 === strpos($link, 'http')) ? $link : rtrim($baseUrl, '/') . $link) : null;

        $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $safeBody = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

        $htmlBody = '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;">'
            . '<h2 style="color:#225b7b;margin-top:0;">' . $safeSubject . '</h2>'
            . '<div style="background:#f8f9fa;padding:16px;border-radius:8px;margin:16px 0;">' . $safeBody . '</div>'
            . ($absLink
                ? '<p><a href="' . htmlspecialchars($absLink, ENT_QUOTES, 'UTF-8') . '" '
                  . 'style="background:#225b7b;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;display:inline-block;">View</a></p>'
                : '')
            . '<hr style="margin-top:30px;">'
            . '<p style="font-size:12px;color:#999;">AtoM Registry notification &middot; '
            . '<a href="' . htmlspecialchars(rtrim($baseUrl, '/') . '/registry/notifications', ENT_QUOTES, 'UTF-8') . '">Manage notifications</a></p>'
            . '</div>';

        $fromEmail = $smtp['smtp_from_email'] ?: 'noreply@theahg.co.za';
        $fromName = $smtp['smtp_from_name'] ?: 'AtoM Registry';

        if (!empty($smtp['smtp_enabled'])) {
            if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                $autoload = \sfConfig::get('sf_root_dir') . '/atom-framework/vendor/autoload.php';
                if (file_exists($autoload)) {
                    require_once $autoload;
                }
            }
        }

        if (!empty($smtp['smtp_enabled']) && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $smtp['smtp_host'];
                $mail->Port = (int) $smtp['smtp_port'];
                $mail->SMTPAuth = true;
                $mail->Username = $smtp['smtp_username'];
                $mail->Password = $smtp['smtp_password'];
                if ('tls' === $smtp['smtp_encryption']) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                } elseif ('ssl' === $smtp['smtp_encryption']) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                }
                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = '[AtoM Registry] ' . $subject;
                $mail->Body = $htmlBody;
                $mail->send();

                return true;
            } catch (\Throwable $e) {
                error_log('Registry notification SMTP error: ' . $e->getMessage());

                return false;
            }
        }

        // Fallback to mail()
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
        ];

        return @mail($to, '[AtoM Registry] ' . $subject, $htmlBody, implode("\r\n", $headers));
    }

    protected function getSmtpSettings(): array
    {
        $keys = ['smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password', 'smtp_from_email', 'smtp_from_name'];
        $settings = [];
        foreach ($keys as $key) {
            $row = DB::table('registry_settings')->where('setting_key', $key)->first();
            $settings[$key] = $row ? $row->setting_value : '';
        }

        return $settings;
    }
}
