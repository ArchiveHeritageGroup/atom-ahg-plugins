<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class NewsletterService
{
    // =========================================================================
    // Subscriber Management
    // =========================================================================

    public function subscribe(array $data): array
    {
        $email = strtolower(trim($data['email'] ?? ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Valid email address is required.'];
        }

        $existing = DB::table('registry_newsletter_subscriber')
            ->where('email', $email)->first();

        if ($existing) {
            if ('unsubscribed' === $existing->status) {
                DB::table('registry_newsletter_subscriber')
                    ->where('id', $existing->id)
                    ->update([
                        'status' => 'active',
                        'name' => $data['name'] ?? $existing->name,
                        'user_id' => $data['user_id'] ?? $existing->user_id,
                        'institution_id' => $data['institution_id'] ?? $existing->institution_id,
                        'vendor_id' => $data['vendor_id'] ?? $existing->vendor_id,
                        'unsubscribed_at' => null,
                        'is_confirmed' => 1,
                        'subscribed_at' => date('Y-m-d H:i:s'),
                    ]);

                return ['success' => true, 'resubscribed' => true];
            }

            return ['success' => true, 'already_subscribed' => true];
        }

        $token = bin2hex(random_bytes(32));
        $confirmToken = bin2hex(random_bytes(32));

        DB::table('registry_newsletter_subscriber')->insert([
            'email' => $email,
            'name' => $data['name'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'institution_id' => $data['institution_id'] ?? null,
            'vendor_id' => $data['vendor_id'] ?? null,
            'unsubscribe_token' => $token,
            'confirm_token' => $confirmToken,
            'is_confirmed' => !empty($data['auto_confirm']) ? 1 : 0,
            'status' => 'active',
            'subscribed_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'confirm_token' => $confirmToken];
    }

    public function unsubscribe(string $token): array
    {
        $sub = DB::table('registry_newsletter_subscriber')
            ->where('unsubscribe_token', $token)
            ->first();

        if (!$sub) {
            return ['success' => false, 'error' => 'Invalid unsubscribe link.'];
        }

        DB::table('registry_newsletter_subscriber')
            ->where('id', $sub->id)
            ->update([
                'status' => 'unsubscribed',
                'unsubscribed_at' => date('Y-m-d H:i:s'),
            ]);

        return ['success' => true, 'email' => $sub->email];
    }

    public function confirm(string $token): array
    {
        $sub = DB::table('registry_newsletter_subscriber')
            ->where('confirm_token', $token)
            ->first();

        if (!$sub) {
            return ['success' => false, 'error' => 'Invalid confirmation link.'];
        }

        DB::table('registry_newsletter_subscriber')
            ->where('id', $sub->id)
            ->update(['is_confirmed' => 1, 'confirm_token' => null]);

        return ['success' => true, 'email' => $sub->email];
    }

    public function getSubscriberStats(): array
    {
        $total = DB::table('registry_newsletter_subscriber')->count();
        $active = DB::table('registry_newsletter_subscriber')->where('status', 'active')->count();
        $unsubscribed = DB::table('registry_newsletter_subscriber')->where('status', 'unsubscribed')->count();

        return ['total' => $total, 'active' => $active, 'unsubscribed' => $unsubscribed];
    }

    public function browseSubscribers(array $params = []): array
    {
        $query = DB::table('registry_newsletter_subscriber');

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (!empty($params['search'])) {
            $like = '%' . $params['search'] . '%';
            $query->where(function ($q) use ($like) {
                $q->where('email', 'LIKE', $like)
                  ->orWhere('name', 'LIKE', $like);
            });
        }

        $total = $query->count();
        $limit = $params['limit'] ?? 50;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy('subscribed_at', 'desc')
            ->limit($limit)->offset($offset)->get()->all();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    // =========================================================================
    // Newsletter CRUD
    // =========================================================================

    public function create(array $data): array
    {
        if (empty($data['subject'])) {
            return ['success' => false, 'error' => 'Subject is required.'];
        }
        if (empty($data['content'])) {
            return ['success' => false, 'error' => 'Content is required.'];
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 'draft';

        $id = DB::table('registry_newsletter')->insertGetId($data);

        return ['success' => true, 'id' => $id];
    }

    public function update(int $id, array $data): array
    {
        $nl = DB::table('registry_newsletter')->where('id', $id)->first();
        if (!$nl) {
            return ['success' => false, 'error' => 'Newsletter not found.'];
        }
        if ('sent' === $nl->status) {
            return ['success' => false, 'error' => 'Cannot edit a sent newsletter.'];
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::table('registry_newsletter')->where('id', $id)->update($data);

        return ['success' => true];
    }

    public function findById(int $id): ?object
    {
        return DB::table('registry_newsletter')->where('id', $id)->first();
    }

    public function browse(array $params = []): array
    {
        $query = DB::table('registry_newsletter');

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $total = $query->count();
        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy('created_at', 'desc')
            ->limit($limit)->offset($offset)->get()->all();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    // =========================================================================
    // Sending
    // =========================================================================

    public function send(int $newsletterId): array
    {
        $nl = DB::table('registry_newsletter')->where('id', $newsletterId)->first();
        if (!$nl) {
            return ['success' => false, 'error' => 'Newsletter not found.'];
        }
        if ('sent' === $nl->status) {
            return ['success' => false, 'error' => 'Newsletter already sent.'];
        }

        $subscribers = DB::table('registry_newsletter_subscriber')
            ->where('status', 'active')
            ->where('is_confirmed', 1)
            ->get()->all();

        $sent = 0;
        $failed = 0;

        foreach ($subscribers as $sub) {
            $success = $this->sendToSubscriber($nl, $sub);

            DB::table('registry_newsletter_send_log')->insert([
                'newsletter_id' => $nl->id,
                'subscriber_id' => $sub->id,
                'status' => $success ? 'sent' : 'failed',
                'sent_at' => $success ? date('Y-m-d H:i:s') : null,
                'error_message' => $success ? null : 'Mail send failed',
            ]);

            if ($success) {
                $sent++;
            } else {
                $failed++;
            }
        }

        DB::table('registry_newsletter')->where('id', $nl->id)->update([
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
            'recipient_count' => count($subscribers),
            'sent_count' => $sent,
        ]);

        return ['success' => true, 'sent' => $sent, 'failed' => $failed, 'total' => count($subscribers)];
    }

    public function getSmtpSettings(): array
    {
        $keys = ['smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password', 'smtp_from_email', 'smtp_from_name'];
        $settings = [];
        foreach ($keys as $key) {
            $row = DB::table('registry_settings')->where('setting_key', $key)->first();
            $settings[$key] = $row ? $row->setting_value : '';
        }

        return $settings;
    }

    protected function sendToSubscriber(object $newsletter, object $subscriber): bool
    {
        $unsubscribeUrl = \sfConfig::get('app_registry_base_url', '')
            . '/registry/newsletter/unsubscribe?token=' . $subscriber->unsubscribe_token;

        $htmlContent = $newsletter->content;
        $htmlContent .= '<hr style="margin-top:30px;"><p style="font-size:12px;color:#999;">'
            . 'You received this because you are subscribed to the AtoM Registry newsletter.<br>'
            . '<a href="' . htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8') . '">Unsubscribe</a></p>';

        $smtp = $this->getSmtpSettings();

        // Use PHPMailer with SMTP if available and enabled
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
                $mail->setFrom($smtp['smtp_from_email'] ?: 'noreply@theahg.co.za', $smtp['smtp_from_name'] ?: 'AtoM Registry');
                $mail->addAddress($subscriber->email);
                $mail->addCustomHeader('List-Unsubscribe', '<' . $unsubscribeUrl . '>');
                $mail->isHTML(true);
                $mail->Subject = $newsletter->subject;
                $mail->Body = $htmlContent;
                $mail->send();

                return true;
            } catch (\Exception $e) {
                error_log('Registry newsletter SMTP error: ' . $e->getMessage());

                return false;
            }
        }

        // Fallback to mail()
        $fromEmail = $smtp['smtp_from_email'] ?: 'noreply@theahg.co.za';
        $fromName = $smtp['smtp_from_name'] ?: 'AtoM Registry';

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'List-Unsubscribe: <' . $unsubscribeUrl . '>',
        ];

        return @mail(
            $subscriber->email,
            $newsletter->subject,
            $htmlContent,
            implode("\r\n", $headers)
        );
    }

    public function delete(int $id): array
    {
        $nl = DB::table('registry_newsletter')->where('id', $id)->first();
        if (!$nl) {
            return ['success' => false, 'error' => 'Newsletter not found.'];
        }

        DB::table('registry_newsletter_send_log')->where('newsletter_id', $id)->delete();
        DB::table('registry_newsletter')->where('id', $id)->delete();

        return ['success' => true];
    }
}
