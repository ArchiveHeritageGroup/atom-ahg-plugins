<?php

declare(strict_types=1);

namespace AhgLoan\Services\Loan;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Loan Agreement Generator.
 *
 * Generates loan agreement documents in various formats.
 * Supports customizable templates with variable substitution.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class LoanAgreementGenerator
{
    /** Agreement templates */
    public const TEMPLATE_STANDARD = 'standard';
    public const TEMPLATE_SHORT = 'short';
    public const TEMPLATE_INTERNATIONAL = 'international';
    public const TEMPLATE_RESEARCH = 'research';

    /** Output formats */
    public const FORMAT_HTML = 'html';
    public const FORMAT_PDF = 'pdf';
    public const FORMAT_DOCX = 'docx';

    private LoanService $loanService;
    private LoggerInterface $logger;
    private string $templateDir;

    /** @var array Institution details */
    private array $institution = [
        'name' => 'The Archive and Heritage Group',
        'address' => '',
        'phone' => '',
        'email' => '',
        'website' => 'https://theahg.co.za',
    ];

    public function __construct(
        LoanService $loanService,
        string $templateDir = '',
        ?LoggerInterface $logger = null
    ) {
        $this->loanService = $loanService;
        $this->templateDir = $templateDir ?: __DIR__.'/../../../templates/loan';
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Set institution details.
     */
    public function setInstitution(array $details): self
    {
        $this->institution = array_merge($this->institution, $details);

        return $this;
    }

    /**
     * Generate loan agreement.
     */
    public function generate(
        int $loanId,
        string $template = self::TEMPLATE_STANDARD,
        string $format = self::FORMAT_HTML,
        array $options = []
    ): string {
        $loan = $this->loanService->get($loanId);
        if (!$loan) {
            throw new \InvalidArgumentException("Loan not found: {$loanId}");
        }

        $variables = $this->prepareVariables($loan, $options);
        $templateContent = $this->getBuiltInTemplate($template, $loan['loan_type']);
        $content = $this->substituteVariables($templateContent, $variables);

        return $this->convertFormat($content, $format, $variables);
    }

    /**
     * Prepare template variables from loan data.
     */
    private function prepareVariables(array $loan, array $options): array
    {
        $objects = $loan['objects'] ?? [];
        $totalInsurance = array_sum(array_column($objects, 'insurance_value'));

        if (!$totalInsurance && $loan['insurance_value']) {
            $totalInsurance = $loan['insurance_value'];
        }

        $objectListHtml = $this->buildObjectListHtml($objects);

        return [
            'loan_number' => $loan['loan_number'],
            'loan_type' => $loan['loan_type'],
            'purpose' => LoanService::PURPOSES[$loan['purpose']] ?? $loan['purpose'],
            'title' => $loan['title'] ?? '',
            'description' => $loan['description'] ?? '',
            'request_date' => $this->formatDate($loan['request_date']),
            'start_date' => $this->formatDate($loan['start_date']),
            'end_date' => $this->formatDate($loan['end_date']),
            'loan_period' => $this->calculatePeriod($loan['start_date'], $loan['end_date']),
            'current_date' => date('j F Y'),
            'partner_institution' => $loan['partner_institution'],
            'partner_contact_name' => $loan['partner_contact_name'] ?? '',
            'partner_contact_email' => $loan['partner_contact_email'] ?? '',
            'partner_contact_phone' => $loan['partner_contact_phone'] ?? '',
            'partner_address' => $loan['partner_address'] ?? '',
            'institution_name' => $this->institution['name'],
            'institution_address' => $this->institution['address'],
            'institution_phone' => $this->institution['phone'],
            'institution_email' => $this->institution['email'],
            'lender_name' => 'out' === $loan['loan_type']
                ? $this->institution['name']
                : $loan['partner_institution'],
            'borrower_name' => 'out' === $loan['loan_type']
                ? $loan['partner_institution']
                : $this->institution['name'],
            'insurance_type' => LoanService::INSURANCE_TYPES[$loan['insurance_type']] ?? $loan['insurance_type'],
            'insurance_value' => $this->formatCurrency($loan['insurance_value'], $loan['insurance_currency']),
            'total_insurance_value' => $this->formatCurrency($totalInsurance, $loan['insurance_currency']),
            'object_count' => count($objects),
            'object_list_html' => $objectListHtml,
            'objects' => $objects,
        ];
    }

    /**
     * Build HTML table of loan objects.
     */
    private function buildObjectListHtml(array $objects): string
    {
        if (empty($objects)) {
            return '<p><em>No objects specified</em></p>';
        }

        $html = '<table class="object-list" style="width:100%; border-collapse:collapse;">';
        $html .= '<thead><tr style="background:#f0f0f0;">';
        $html .= '<th style="border:1px solid #ccc; padding:8px;">No.</th>';
        $html .= '<th style="border:1px solid #ccc; padding:8px;">Identifier</th>';
        $html .= '<th style="border:1px solid #ccc; padding:8px;">Title/Description</th>';
        $html .= '<th style="border:1px solid #ccc; padding:8px; text-align:right;">Insurance Value</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($objects as $i => $obj) {
            $html .= '<tr>';
            $html .= '<td style="border:1px solid #ccc; padding:8px;">'.($i + 1).'</td>';
            $html .= '<td style="border:1px solid #ccc; padding:8px;">'.htmlspecialchars($obj['object_identifier'] ?? $obj['identifier'] ?? '').'</td>';
            $html .= '<td style="border:1px solid #ccc; padding:8px;">'.htmlspecialchars($obj['object_title'] ?? $obj['io_title'] ?? '').'</td>';
            $html .= '<td style="border:1px solid #ccc; padding:8px; text-align:right;">'.($obj['insurance_value'] ? number_format($obj['insurance_value'], 2) : '-').'</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Get built-in template.
     */
    private function getBuiltInTemplate(string $template, string $loanType): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Loan Agreement - {{loan_number}}</title>
    <style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
        body { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.5; margin: 2cm; }
        h1 { text-align: center; font-size: 16pt; margin-bottom: 20px; }
        h2 { font-size: 12pt; margin-top: 20px; border-bottom: 1px solid #000; }
        .header { text-align: center; margin-bottom: 30px; }
        .section { margin-bottom: 20px; }
        .parties { display: flex; justify-content: space-between; margin: 20px 0; }
        .party { width: 45%; }
        .signature-block { margin-top: 50px; }
        .signature-line { border-top: 1px solid #000; width: 250px; margin-top: 40px; padding-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        .terms { font-size: 10pt; }
        .terms ol { padding-left: 20px; }
        .terms li { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LOAN AGREEMENT</h1>
        <p><strong>Agreement Number:</strong> {{loan_number}}</p>
        <p><strong>Date:</strong> {{current_date}}</p>
    </div>

    <div class="section">
        <h2>1. Parties</h2>
        <table>
            <tr>
                <td width="50%"><strong>LENDER:</strong><br>{{lender_name}}</td>
                <td width="50%"><strong>BORROWER:</strong><br>{{borrower_name}}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>2. Loan Details</h2>
        <table>
            <tr><td width="30%"><strong>Purpose:</strong></td><td>{{purpose}}</td></tr>
            <tr><td><strong>Exhibition/Project:</strong></td><td>{{title}}</td></tr>
            <tr><td><strong>Loan Period:</strong></td><td>{{start_date}} to {{end_date}} ({{loan_period}})</td></tr>
            <tr><td><strong>Insurance:</strong></td><td>{{insurance_type}}</td></tr>
            <tr><td><strong>Total Value:</strong></td><td>{{total_insurance_value}}</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>3. Objects on Loan ({{object_count}} items)</h2>
        {{object_list_html}}
    </div>

    <div class="section terms">
        <h2>4. Terms and Conditions</h2>
        <ol>
            <li><strong>Care:</strong> The Borrower agrees to exercise the same care with respect to the loaned objects as it does in the safekeeping of comparable property of its own.</li>
            <li><strong>Insurance:</strong> Objects shall be insured for the agreed value against all risks from the time they leave the Lender until return.</li>
            <li><strong>Credit Line:</strong> The Borrower shall acknowledge the loan in all publications and labels.</li>
            <li><strong>Photography:</strong> Objects may not be photographed for publication without written permission.</li>
            <li><strong>Condition Reports:</strong> The Borrower shall complete condition reports upon receipt and before return.</li>
            <li><strong>No Alterations:</strong> The Borrower shall not clean, repair, or alter the objects without written permission.</li>
            <li><strong>Return:</strong> Objects shall be returned by the agreed date in the same condition as received.</li>
            <li><strong>Costs:</strong> The Borrower is responsible for packing, transport, and insurance costs unless otherwise agreed.</li>
        </ol>
    </div>

    <div class="section signature-block">
        <h2>5. Signatures</h2>
        <table>
            <tr>
                <td width="50%">
                    <strong>For the Lender:</strong><br><br>
                    Signature: _________________________<br><br>
                    Name: _________________________<br><br>
                    Title: _________________________<br><br>
                    Date: _________________________
                </td>
                <td width="50%">
                    <strong>For the Borrower:</strong><br><br>
                    Signature: _________________________<br><br>
                    Name: _________________________<br><br>
                    Title: _________________________<br><br>
                    Date: _________________________
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Substitute variables in template.
     */
    private function substituteVariables(string $template, array $variables): string
    {
        return preg_replace_callback(
            '/\{\{(\w+)\}\}/',
            function ($matches) use ($variables) {
                return $variables[$matches[1]] ?? '';
            },
            $template
        );
    }

    /**
     * Convert content to requested format.
     */
    private function convertFormat(string $htmlContent, string $format, array $variables): string
    {
        if (self::FORMAT_PDF === $format) {
            // Ensure composer autoloader is loaded for Dompdf
            $autoloader = class_exists('\AtomFramework\Helpers\PathResolver')
                ? \AtomFramework\Helpers\PathResolver::getRootAutoloadPath()
                : \sfConfig::get('sf_root_dir') . '/vendor/autoload.php';
            if (file_exists($autoloader)) {
                require_once $autoloader;
            }

            if (class_exists('Dompdf\Dompdf')) {
                $dompdf = new \Dompdf\Dompdf([
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'serif',
                ]);
                $dompdf->loadHtml($htmlContent);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                return $dompdf->output();
            }
        }

        return $htmlContent;
    }

    /**
     * Format date for display.
     */
    private function formatDate(?string $date): string
    {
        if (!$date) {
            return 'TBD';
        }

        try {
            return (new \DateTime($date))->format('j F Y');
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Format currency value.
     */
    private function formatCurrency(?float $amount, ?string $currency): string
    {
        if (null === $amount) {
            return '-';
        }

        $symbols = ['ZAR' => 'R', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'];
        $symbol = $symbols[$currency ?? 'ZAR'] ?? $currency.' ';

        return $symbol.number_format($amount, 2);
    }

    /**
     * Calculate loan period description.
     */
    private function calculatePeriod(?string $start, ?string $end): string
    {
        if (!$start || !$end) {
            return 'TBD';
        }

        try {
            $diff = (new \DateTime($start))->diff(new \DateTime($end));
            $parts = [];

            if ($diff->y > 0) {
                $parts[] = $diff->y.' year'.($diff->y > 1 ? 's' : '');
            }
            if ($diff->m > 0) {
                $parts[] = $diff->m.' month'.($diff->m > 1 ? 's' : '');
            }
            if ($diff->d > 0 && empty($parts)) {
                $parts[] = $diff->d.' day'.($diff->d > 1 ? 's' : '');
            }

            return implode(', ', $parts) ?: '1 day';
        } catch (\Exception $e) {
            return 'TBD';
        }
    }

    /**
     * Get available templates.
     */
    public function getTemplates(): array
    {
        return [
            self::TEMPLATE_STANDARD => 'Standard Loan Agreement',
            self::TEMPLATE_SHORT => 'Short Form Agreement',
            self::TEMPLATE_INTERNATIONAL => 'International Loan Agreement',
            self::TEMPLATE_RESEARCH => 'Research/Study Loan',
        ];
    }
}
