<?php
/**
 * Database-driven Heritage Compliance Service
 * Reads compliance rules from heritage_compliance_rule table
 */

use Illuminate\Database\Capsule\Manager as DB;

class HeritageComplianceService
{
    /**
     * Check compliance for an asset against its accounting standard
     */
    public function checkCompliance(object $asset): array
    {
        if (empty($asset->accounting_standard_id)) {
            return [
                'compliant' => false,
                'score' => 0,
                'issues' => [['severity' => 'error', 'message' => 'No accounting standard assigned', 'category' => 'recognition']],
                'passed' => [],
                'summary' => ['errors' => 1, 'warnings' => 0, 'info' => 0]
            ];
        }

        $rules = DB::table('heritage_compliance_rule')
            ->where('standard_id', $asset->accounting_standard_id)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();

        $issues = [];
        $passed = [];

        foreach ($rules as $rule) {
            $result = $this->evaluateRule($rule, $asset);
            
            if ($result['passed']) {
                $passed[] = [
                    'code' => $rule->code,
                    'name' => $rule->name,
                    'category' => $rule->category,
                    'reference' => $rule->reference
                ];
            } else {
                $issues[] = [
                    'code' => $rule->code,
                    'name' => $rule->name,
                    'category' => $rule->category,
                    'severity' => $rule->severity,
                    'message' => $rule->error_message,
                    'reference' => $rule->reference,
                    'field' => $rule->field_name
                ];
            }
        }

        $summary = [
            'errors' => count(array_filter($issues, fn($i) => $i['severity'] === 'error')),
            'warnings' => count(array_filter($issues, fn($i) => $i['severity'] === 'warning')),
            'info' => count(array_filter($issues, fn($i) => $i['severity'] === 'info'))
        ];

        $totalRules = count($rules);
        $passedCount = count($passed);
        $score = $totalRules > 0 ? round(($passedCount / $totalRules) * 100) : 0;

        return [
            'compliant' => $summary['errors'] === 0,
            'score' => $score,
            'issues' => $issues,
            'passed' => $passed,
            'summary' => $summary,
            'total_rules' => $totalRules,
            'passed_count' => $passedCount
        ];
    }

    /**
     * Evaluate a single rule against an asset
     */
    protected function evaluateRule(object $rule, object $asset): array
    {
        $passed = false;
        $fieldName = $rule->field_name;
        $value = $asset->$fieldName ?? null;

        switch ($rule->check_type) {
            case 'required_field':
                $passed = !empty($value);
                break;

            case 'value_check':
                if ($rule->condition) {
                    // Parse condition like ">0", ">=1", "!=0"
                    if (preg_match('/^([><=!]+)(\d+\.?\d*)$/', $rule->condition, $matches)) {
                        $operator = $matches[1];
                        $threshold = (float)$matches[2];
                        $numValue = (float)$value;
                        
                        $passed = match($operator) {
                            '>' => $numValue > $threshold,
                            '>=' => $numValue >= $threshold,
                            '<' => $numValue < $threshold,
                            '<=' => $numValue <= $threshold,
                            '=' => $numValue == $threshold,
                            '==' => $numValue == $threshold,
                            '!=' => $numValue != $threshold,
                            default => false
                        };
                    }
                }
                break;

            case 'date_check':
                $passed = !empty($value) && strtotime($value) !== false;
                break;

            case 'custom':
                // Custom rules can be extended
                $passed = $this->evaluateCustomRule($rule, $asset);
                break;

            default:
                $passed = !empty($value);
        }

        return ['passed' => $passed, 'value' => $value];
    }

    /**
     * Evaluate custom rules (can be extended)
     */
    protected function evaluateCustomRule(object $rule, object $asset): bool
    {
        // Override in subclass for custom logic
        return true;
    }

    /**
     * Get compliance summary for multiple assets by standard
     */
    public function getComplianceSummary(int $standardId = null): array
    {
        $query = DB::table('heritage_asset');
        
        if ($standardId) {
            $query->where('accounting_standard_id', $standardId);
        }

        $assets = $query->get();
        
        $summary = [
            'total' => count($assets),
            'compliant' => 0,
            'non_compliant' => 0,
            'by_category' => [
                'recognition' => ['passed' => 0, 'failed' => 0],
                'measurement' => ['passed' => 0, 'failed' => 0],
                'disclosure' => ['passed' => 0, 'failed' => 0]
            ],
            'common_issues' => []
        ];

        $issueCount = [];

        foreach ($assets as $asset) {
            $result = $this->checkCompliance($asset);
            
            if ($result['compliant']) {
                $summary['compliant']++;
            } else {
                $summary['non_compliant']++;
            }

            foreach ($result['issues'] as $issue) {
                $key = $issue['code'];
                $issueCount[$key] = ($issueCount[$key] ?? 0) + 1;
                
                if (!isset($summary['common_issues'][$key])) {
                    $summary['common_issues'][$key] = [
                        'code' => $issue['code'],
                        'name' => $issue['name'],
                        'message' => $issue['message'],
                        'count' => 0
                    ];
                }
                $summary['common_issues'][$key]['count']++;
            }
        }

        // Sort common issues by count
        uasort($summary['common_issues'], fn($a, $b) => $b['count'] - $a['count']);
        $summary['common_issues'] = array_values($summary['common_issues']);

        return $summary;
    }

    /**
     * Get rules for a standard
     */
    public function getRulesForStandard(int $standardId): array
    {
        return DB::table('heritage_compliance_rule')
            ->where('standard_id', $standardId)
            ->where('is_active', 1)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Get all standards with their rule counts
     */
    public function getStandardsWithRuleCounts(): array
    {
        return DB::table('heritage_accounting_standard as s')
            ->leftJoin('heritage_compliance_rule as r', function($join) {
                $join->on('s.id', '=', 'r.standard_id')
                     ->where('r.is_active', 1);
            })
            ->select(
                's.id', 's.code', 's.name', 's.country',
                DB::raw('COUNT(r.id) as rule_count'),
                DB::raw('SUM(CASE WHEN r.severity = "error" THEN 1 ELSE 0 END) as error_rules'),
                DB::raw('SUM(CASE WHEN r.severity = "warning" THEN 1 ELSE 0 END) as warning_rules'),
                DB::raw('SUM(CASE WHEN r.severity = "info" THEN 1 ELSE 0 END) as info_rules')
            )
            ->where('s.is_active', 1)
            ->groupBy('s.id', 's.code', 's.name', 's.country')
            ->orderBy('s.sort_order')
            ->get()
            ->toArray();
    }
}
