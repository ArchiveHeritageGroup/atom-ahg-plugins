<?php

declare(strict_types=1);

namespace ahgLibraryPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

/**
 * OverdueNoticeService — generates and sends overdue item notices.
 *
 * Workflow:
 *   1. getOverdueItems()      — fetch overdue loans grouped by patron
 *   2. generateNoticeHtml()    — render an HTML notice for one patron
 *   3. sendBatchNotices()      — send all outstanding overdue notices
 *   4. sendSingleNotice()      — send a notice for one patron
 *
 * Email is sent via Symfony 1.4 sfMailer.
 * Notice templates use PHP string-replacement merge fields.
 *
 * @package ahgLibraryPlugin\Service
 */
class OverdueNoticeService
{
    protected static ?OverdueNoticeService $instance = null;
    protected Logger $logger;
    protected string $culture;

    // Fine thresholds (days overdue)
    public const LEVEL_1_DAYS = 7;
    public const LEVEL_2_DAYS = 14;
    public const LEVEL_3_DAYS = 30;

    public function __construct(?string $culture = null)
    {
        $this->culture = $culture ?? (\sfContext::hasInstance()
            ? \sfContext::getInstance()->getUser()->getCulture()
            : 'en');
        $this->initLogger();
    }

    public static function getInstance(?string $culture = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($culture);
        }
        return self::$instance;
    }

    protected function initLogger(): void
    {
        $this->logger = new Logger('library.overdue');
        $logPath = \sfConfig::get('sf_log_dir', '/tmp') . '/library.log';
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath, 30, Logger::INFO)
        );
    }

    // ========================================================================
    // Fetch overdue items
    // ========================================================================

    /**
     * Fetch overdue items grouped by patron.
     *
     * @param int|null $limit
     * @param int|null $offset
     * @param int|null $minDays  Minimum days overdue (default: 1)
     * @return array{patrons: array, total: int}
     */
    public function getOverdueItems(?int $limit = null, ?int $offset = null, int $minDays = 1): array
    {
        $today = date('Y-m-d');

        $query = DB::table('library_checkout as c')
            ->join('library_copy as cp', 'c.copy_id', '=', 'cp.id')
            ->join('library_item as li', 'cp.library_item_id', '=', 'li.id')
            ->leftJoin('library_patron as p', 'c.patron_id', '=', 'p.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('li.information_object_id', '=', 'ioi.id')
                  ->where('ioi.culture', '=', $this->culture);
            })
            ->whereNull('c.return_date')
            ->where('c.due_date', '<', $today)
            ->whereRaw("DATEDIFF('{$today}', c.due_date) >= ?", [$minDays])
            ->select([
                'c.id as circulation_id',
                'c.checkout_date',
                'c.due_date',
                'c.renewed_count as renewals',
                'cp.barcode as item_barcode',
                'li.call_number',
                'ioi.title as item_title',
                'p.id as patron_id',
                'p.card_number as patron_barcode',
                DB::raw("CONCAT(COALESCE(p.first_name,''), ' ', COALESCE(p.last_name,'')) as patron_name"),
                'p.email as patron_email',
                DB::raw("DATEDIFF('{$today}', c.due_date) as days_overdue"),
            ])
            ->orderBy('c.due_date', 'asc');

        $total = (clone $query)->count();

        if ($limit !== null) {
            $query->limit($limit);
        }
        if ($offset !== null) {
            $query->offset($offset);
        }

        $rows = $query->get()->toArray();

        // Group by patron
        $patrons = [];
        foreach ($rows as $row) {
            $pid = $row->patron_id ?? 'anon_' . ($row->patron_barcode ?? $row->circulation_id);
            if (!isset($patrons[$pid])) {
                $patrons[$pid] = [
                    'patron_id'       => $row->patron_id,
                    'patron_name'     => trim($row->patron_name ?? ''),
                    'patron_email'    => $row->patron_email ?? '',
                    'patron_barcode'  => $row->patron_barcode ?? '',
                    'items'           => [],
                ];
            }
            $patrons[$pid]['items'][] = [
                'circulation_id'  => $row->circulation_id,
                'item_title'      => $row->item_title ?? '',
                'item_barcode'    => $row->item_barcode ?? '',
                'call_number'     => $row->call_number ?? '',
                'checkout_date'   => $row->checkout_date ?? '',
                'due_date'        => $row->due_date ?? '',
                'days_overdue'    => (int) $row->days_overdue,
                'renewals'        => (int) $row->renewals,
            ];
        }

        return ['patrons' => array_values($patrons), 'total' => (int) $total];
    }

    /**
     * Get overdue patron summary with aggregate stats.
     *
     * @return array{patron_id: int|null, patron_name: string, patron_email: string,
     *               patron_barcode: string, item_count: int, max_days_overdue: int,
     *               has_email: bool, items: array}
     */
    public function getOverdueSummaryByPatron(): array
    {
        $result = $this->getOverdueItems();
        return $result['patrons'];
    }

    // ========================================================================
    // Notice templates
    // ========================================================================

    /**
     * Get the overdue notice HTML template.
     * Merge fields: {{patron_name}}, {{patron_barcode}}, {{today}},
     * {{items_table}}, {{total_fines}}, {{library_name}}, {{library_address}},
     * {{library_phone}}, {{library_email}}, {{days_label}}
     *
     * @return string HTML template
     */
    public function getNoticeTemplate(): string
    {
        $libraryName = \sfConfig::get('app_library_name', 'Library');
        $libraryEmail = \sfConfig::get('app_library_email', 'library@theahg.co.za');
        $libraryAddress = \sfConfig::get('app_library_address', '');

        $template = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Overdue Notice — {$libraryName}</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 14px; color: #222; margin: 0; padding: 20px; background: #f9f9f9; }
    .wrapper { max-width: 700px; margin: 0 auto; background: #fff; border: 1px solid #ddd; border-radius: 6px; overflow: hidden; }
    .header { background: #0d6efd; color: #fff; padding: 20px 30px; }
    .header h1 { margin: 0; font-size: 20px; }
    .header p { margin: 4px 0 0; opacity: 0.85; font-size: 13px; }
    .body { padding: 25px 30px; }
    .patron-info { background: #f0f7ff; border: 1px solid #cce5ff; border-radius: 4px; padding: 12px 16px; margin-bottom: 20px; }
    .patron-info p { margin: 3px 0; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
    th { background: #e9ecef; text-align: left; padding: 8px 10px; border-bottom: 2px solid #dee2e6; }
    td { padding: 8px 10px; border-bottom: 1px solid #eee; vertical-align: top; }
    tr:hover td { background: #fafafa; }
    .overdue-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .overdue-light { background: #fff3cd; color: #856404; }
    .overdue-medium { background: #ffe8a3; color: #7a4f00; }
    .overdue-heavy { background: #f8d7da; color: #721c24; }
    .notice-footer { background: #f8f9fa; border-top: 1px solid #eee; padding: 15px 30px; font-size: 12px; color: #666; }
    .notice-footer p { margin: 3px 0; }
    .btn { display: inline-block; background: #0d6efd; color: #fff; padding: 8px 20px; border-radius: 4px; text-decoration: none; font-size: 13px; }
    .fine-box { background: #fff8e1; border: 1px solid #ffe082; border-radius: 4px; padding: 12px 16px; margin-top: 15px; }
  </style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Overdue Notice</h1>
    <p>{$libraryName} — Library Circulation System</p>
  </div>

  <div class="body">
    <p>Dear <strong>{{patron_name}}</strong>,</p>

    <p>Our records indicate that the following item(s) borrowed from <strong>{$libraryName}</strong> are now overdue.
       We kindly request that you return them at your earliest convenience.</p>

    <div class="patron-info">
      <p><strong>Patron:</strong> {{patron_name}}</p>
      <p><strong>Library Card:</strong> {{patron_barcode}}</p>
      <p><strong>Notice Date:</strong> {{today}}</p>
    </div>

    <h3 style="margin-bottom:10px;">Overdue Items</h3>
    {{items_table}}

    {{fine_box}}

    <p style="margin-top:20px;">If you have already returned these items, please disregard this notice or contact us
       to correct our records.</p>

    <p>Thank you for your cooperation.</p>
    <p><strong>{$libraryName} Library</strong><br>
       {{library_address}}</p>
  </div>

  <div class="notice-footer">
    <p><strong>Contact us:</strong> {{library_email}} | {{library_phone}}</p>
    <p>This is an automated notice. Please do not reply directly to this email —
       contact the library using the details above.</p>
    <p style="margin-top:8px; color:#aaa; font-size:11px;">
       Circulation record ID: {{circulation_ids}} &bull; Notice generated: {{generated_at}}
    </p>
  </div>
</div>
</body>
</html>
HTML;

        return $template;
    }

    /**
     * Generate overdue notice HTML for a single patron.
     *
     * @param array $patron   One patron from getOverdueItems()
     * @param bool $includeFines  Whether to compute and show fine estimates
     * @return string Rendered HTML
     */
    public function generateNoticeHtml(array $patron, bool $includeFines = true): string
    {
        $template = $this->getNoticeTemplate();

        $today = date('d F Y');
        $patronName = esc_entities($patron['patron_name'] ?: 'Valued Patron');
        $patronBarcode = esc_entities($patron['patron_barcode'] ?: '—');

        $libraryName = \sfConfig::get('app_library_name', 'Library');
        $libraryEmail = \sfConfig::get('app_library_email', 'library@theahg.co.za');
        $libraryPhone = \sfConfig::get('app_library_phone', '');
        $libraryAddress = \sfConfig::get('app_library_address', '');

        // Build items table
        $circulationIds = [];
        $itemsRows = '';
        foreach ($patron['items'] ?? [] as $item) {
            $circulationIds[] = $item['circulation_id'];
            $days = $item['days_overdue'];
            $badgeClass = $days >= self::LEVEL_3_DAYS ? 'overdue-heavy'
                : ($days >= self::LEVEL_2_DAYS ? 'overdue-medium'
                : 'overdue-light');
            $badgeLabel = $days . ' day' . ($days !== 1 ? 's' : '') . ' overdue';

            $itemsRows .= '<tr>'
                . '<td>' . esc_entities($item['item_title'] ?: '—') . '</td>'
                . '<td><code>' . esc_entities($item['item_barcode'] ?: '—') . '</code></td>'
                . '<td>' . esc_entities($item['call_number'] ?: '—') . '</td>'
                . '<td>' . esc_entities(substr($item['checkout_date'], 0, 10)) . '</td>'
                . '<td>' . esc_entities(substr($item['due_date'], 0, 10)) . '</td>'
                . '<td><span class="overdue-badge ' . $badgeClass . '">' . $badgeLabel . '</span></td>'
                . '</tr>' . "\n";
        }

        $itemsTable = '<table>'
            . '<thead><tr>'
            . '<th>Title</th><th>Barcode</th><th>Call No.</th>'
            . '<th>Out</th><th>Due</th><th>Status</th>'
            . '</tr></thead><tbody>' . "\n"
            . $itemsRows
            . '</tbody></table>';

        // Fine box
        $fineBox = '';
        if ($includeFines) {
            $fineBox = $this->buildFineBox($patron['items'] ?? []);
        }

        // Merge fields
        $html = $template;
        $html = str_replace('{{patron_name}}', $patronName, $html);
        $html = str_replace('{{patron_barcode}}', $patronBarcode, $html);
        $html = str_replace('{{today}}', $today, $html);
        $html = str_replace('{{items_table}}', $itemsTable, $html);
        $html = str_replace('{{fine_box}}', $fineBox, $html);
        $html = str_replace('{{library_name}}', esc_entities($libraryName), $html);
        $html = str_replace('{{library_email}}', esc_entities($libraryEmail), $html);
        $html = str_replace('{{library_phone}}', esc_entities($libraryPhone), $html);
        $html = str_replace('{{library_address}}', esc_entities($libraryAddress), $html);
        $html = str_replace('{{circulation_ids}}', implode(', ', $circulationIds), $html);
        $html = str_replace('{{generated_at}}', date('Y-m-d H:i:s'), $html);

        return $html;
    }

    /**
     * Build the fine estimate box HTML.
     *
     * @param array $items
     * @return string HTML block (empty string if no fines applicable)
     */
    protected function buildFineBox(array $items): string
    {
        // Grace days before fines apply
        $graceDays = (int) \sfConfig::get('app_library_fine_grace_days', 0);
        $dailyRate = (float) \sfConfig::get('app_library_fine_daily_rate', 0.50);
        $maxFine = (float) \sfConfig::get('app_library_fine_max', 10.00);

        $totalEstimate = 0.0;
        $finedItems = 0;

        foreach ($items as $item) {
            $days = $item['days_overdue'];
            if ($days > $graceDays) {
                $chargeable = $days - $graceDays;
                $itemFine = min($chargeable * $dailyRate, $maxFine);
                $totalEstimate += $itemFine;
                $finedItems++;
            }
        }

        if ($finedItems === 0) {
            return ''; // No fines yet
        }

        $totalStr = number_format($totalEstimate, 2);
        $libraryCurrency = \sfConfig::get('app_library_currency', 'ZAR');

        return <<<HTML
<div class="fine-box">
  <strong>Estimated Fines</strong> (after {$graceDays}-day grace period,
  {$libraryCurrency} {$dailyRate}/day, max {$libraryCurrency} {$maxFine}/item)<br>
  {$finedItems} item(s) may incur fines &mdash; estimated total:
  <strong>{$libraryCurrency} {$totalStr}</strong>
</div>
HTML;
    }

    /**
     * Generate a plain-text version of the notice for email clients
     * that do not render HTML.
     *
     * @param array $patron
     * @return string Plain text
     */
    public function generateNoticeText(array $patron): string
    {
        $libraryName = \sfConfig::get('app_library_name', 'Library');
        $today = date('d F Y');

        $lines = [
            "OVERDUE NOTICE — {$libraryName}",
            str_repeat('=', 50),
            "Date: {$today}",
            "Patron: " . ($patron['patron_name'] ?: 'Valued Patron'),
            "Library Card: " . ($patron['patron_barcode'] ?: '—'),
            "",
            "Dear " . ($patron['patron_name'] ?: 'Valued Patron') . ",",
            "",
            "The following item(s) are overdue. Please return them as soon as possible.",
            "",
        ];

        $lines[] = sprintf(
            "%-45s %-12s %-10s %-12s %-12s",
            "TITLE", "BARCODE", "CALL NO.", "CHECKOUT", "DUE DATE"
        );
        $lines[] = str_repeat('-', 95);

        foreach ($patron['items'] ?? [] as $item) {
            $lines[] = sprintf(
                "%-45s %-12s %-10s %-12s %-12s",
                substr($item['item_title'] ?: '—', 0, 43),
                substr($item['item_barcode'] ?: '—', 0, 10),
                substr($item['call_number'] ?: '—', 0, 8),
                substr($item['checkout_date'], 0, 10),
                substr($item['due_date'], 0, 10)
            );
            $lines[] = "  [OVERDUE: " . $item['days_overdue'] . " day(s)]";
        }

        $lines[] = "";
        $lines[] = "If you have already returned these items, please contact the library.";
        $lines[] = "";
        $lines[] = "Thank you for your cooperation.";
        $lines[] = "{$libraryName} Library";

        return implode("\n", $lines);
    }

    // ========================================================================
    // Sending
    // ========================================================================

    /**
     * Send overdue notice emails to all patrons with overdue items.
     *
     * @param array $options
     *   - dry_run:      bool   If true, returns what would be sent without sending
     *   - min_days:     int    Minimum days overdue (default 1)
     *   - max_recipients: int  Cap on number of emails (default 200)
     *   - from_email:   string  Sender email address
     *   - from_name:    string  Sender display name
     * @return array{sent: int, skipped: int, errors: array, details: array}
     */
    public function sendBatchNotices(array $options = []): array
    {
        $dryRun = !empty($options['dry_run']);
        $minDays = (int) ($options['min_days'] ?? 1);
        $maxRecipients = (int) ($options['max_recipients'] ?? 200);
        $fromEmail = $options['from_email']
            ?? \sfConfig::get('app_library_email', 'library@theahg.co.za');
        $fromName = $options['from_name']
            ?? \sfConfig::get('app_library_name', 'Library');
        $subjectTemplate = $options['subject'] ?? 'Overdue Notice — {{library_name}} ({{today}})';

        $result = $this->getOverdueItems(null, null, $minDays);
        $patrons = $result['patrons'];
        $totalPatrons = count($patrons);

        $sent = 0;
        $skipped = 0;
        $errors = [];
        $details = [];

        if ($totalPatrons === 0) {
            $this->logger->info('No overdue patrons found for notice batch');
            return ['sent' => 0, 'skipped' => 0, 'errors' => [], 'details' => []];
        }

        $mailer = null;
        if (!$dryRun && \sfContext::hasInstance()) {
            $mailer = \sfContext::getInstance()->getMailer();
        }

        foreach (array_slice($patrons, 0, $maxRecipients) as $patron) {
            $email = trim($patron['patron_email'] ?? '');
            $patronId = $patron['patron_id'] ?? 'anon';

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                $errors[] = "Patron {$patronId}: No valid email ({$patron['patron_email']})";
                $details[] = [
                    'patron_id'   => $patronId,
                    'email'       => $email,
                    'status'      => 'skipped',
                    'reason'      => 'no valid email',
                ];
                continue;
            }

            try {
                $html = $this->generateNoticeHtml($patron);
                $text = $this->generateNoticeText($patron);

                $subject = str_replace('{{library_name}}',
                    \sfConfig::get('app_library_name', 'Library'), $subjectTemplate);
                $subject = str_replace('{{today}}', date('d M Y'), $subject);

                if ($dryRun) {
                    $details[] = [
                        'patron_id'   => $patronId,
                        'email'       => $email,
                        'status'      => 'dry_run',
                        'item_count'  => count($patron['items'] ?? []),
                        'subject'     => $subject,
                    ];
                } else {
                    $message = \Swift_Message::newInstance()
                        ->setSubject($subject)
                        ->setFrom([$fromEmail => $fromName])
                        ->setTo([$email])
                        ->setBody($text, 'text/plain')
                        ->addPart($html, 'text/html');

                    $failedRecipients = [];
                    $mailer->send($message, $failedRecipients);

                    if (!empty($failedRecipients)) {
                        $skipped++;
                        $errors[] = "Patron {$patronId}: Failed recipients: " . implode(', ', $failedRecipients);
                        $details[] = [
                            'patron_id'   => $patronId,
                            'email'       => $email,
                            'status'      => 'failed',
                            'reason'      => 'send failed: ' . implode(', ', $failedRecipients),
                        ];
                    } else {
                        $sent++;
                        $this->logger->info('Overdue notice sent', [
                            'patron_id' => $patronId,
                            'email'     => $email,
                            'items'     => count($patron['items'] ?? []),
                        ]);
                        $details[] = [
                            'patron_id'   => $patronId,
                            'email'       => $email,
                            'status'      => 'sent',
                        ];
                    }
                }
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = "Patron {$patronId}: " . $e->getMessage();
                $details[] = [
                    'patron_id'   => $patronId,
                    'email'       => $email,
                    'status'      => 'error',
                    'reason'      => $e->getMessage(),
                ];
            }
        }

        $this->logger->info('Overdue notice batch complete', [
            'sent'    => $sent,
            'skipped' => $skipped,
            'dry_run' => $dryRun,
        ]);

        return [
            'sent'    => $sent,
            'skipped' => $skipped,
            'total_patrons' => $totalPatrons,
            'errors'  => $errors,
            'details' => $details,
        ];
    }

    /**
     * Send a single overdue notice to one patron by patron ID.
     *
     * @param int|null $patronId
     * @param bool $dryRun
     * @return array{success: bool, message: string, email?: string, error?: string}
     */
    public function sendSingleNotice(?int $patronId, bool $dryRun = false): array
    {
        if ($patronId === null) {
            return ['success' => false, 'message' => 'No patron ID provided'];
        }

        $result = $this->getOverdueItems(null, null, 1);
        $patron = null;
        foreach ($result['patrons'] as $p) {
            if ((int) ($p['patron_id'] ?? 0) === $patronId) {
                $patron = $p;
                break;
            }
        }

        if ($patron === null) {
            return ['success' => false, 'message' => "No overdue items found for patron #{$patronId}"];
        }

        $email = trim($patron['patron_email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => "Patron has no valid email",
                'email'   => $email,
            ];
        }

        $html = $this->generateNoticeHtml($patron);
        $text = $this->generateNoticeText($patron);

        $libraryName = \sfConfig::get('app_library_name', 'Library');
        $fromEmail = \sfConfig::get('app_library_email', 'library@theahg.co.za');
        $subject = "Overdue Notice — {$libraryName} (" . date('d M Y') . ")";

        if ($dryRun) {
            return [
                'success' => true,
                'message' => 'Dry run — would send to ' . $email,
                'email'   => $email,
                'item_count' => count($patron['items'] ?? []),
                'subject' => $subject,
            ];
        }

        try {
            if (!\sfContext::hasInstance()) {
                return ['success' => false, 'message' => 'sfContext not available'];
            }

            $mailer = \sfContext::getInstance()->getMailer();
            $message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom([$fromEmail => $libraryName])
                ->setTo([$email])
                ->setBody($text, 'text/plain')
                ->addPart($html, 'text/html');

            $failedRecipients = [];
            $mailer->send($message, $failedRecipients);

            if (!empty($failedRecipients)) {
                return [
                    'success' => false,
                    'message' => 'Send failed',
                    'email'   => $email,
                    'error'   => implode(', ', $failedRecipients),
                ];
            }

            $this->logger->info('Single overdue notice sent', [
                'patron_id' => $patronId,
                'email'     => $email,
            ]);

            return [
                'success' => true,
                'message' => 'Notice sent to ' . $email,
                'email'   => $email,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'email'   => $email,
            ];
        }
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /**
     * Escape HTML entities (for use in template merge).
     */
    protected function escHtml(?string $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// Required for Swift_Message (loaded by Symfony autoloader at runtime)
// @codeCoverageIgnore
if (!class_exists('\Swift_Message') && class_exists('Swift')) {
    class_alias('Swift_Message', '\Swift_Message');
}
