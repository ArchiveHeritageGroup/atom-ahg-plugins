<?php

namespace ahgDataMigrationPlugin\Validation;

/**
 * Validation report class for tracking row/column errors during import validation.
 *
 * This class provides detailed tracking of validation errors, warnings and info
 * messages organized by row and column for clear user feedback.
 */
class AhgValidationReport
{
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_INFO = 'info';

    protected string $filename = '';
    protected string $sectorCode = '';
    protected int $totalRows = 0;
    protected int $validRows = 0;
    protected int $invalidRows = 0;
    protected int $warningRows = 0;

    /** @var array<int, array<string, array<array{message: string, severity: string, rule: string}>>> */
    protected array $rowErrors = [];

    /** @var array{error: int, warning: int, info: int} */
    protected array $counts = [
        'error' => 0,
        'warning' => 0,
        'info' => 0,
    ];

    /** @var array<string, int> */
    protected array $ruleViolations = [];

    protected float $startTime;
    protected ?float $endTime = null;

    public function __construct(string $filename = '', string $sectorCode = '')
    {
        $this->filename = $filename;
        $this->sectorCode = $sectorCode;
        $this->startTime = microtime(true);
    }

    public function addError(int $row, string $column, string $message, string $rule = ''): self
    {
        return $this->addIssue($row, $column, $message, self::SEVERITY_ERROR, $rule);
    }

    public function addWarning(int $row, string $column, string $message, string $rule = ''): self
    {
        return $this->addIssue($row, $column, $message, self::SEVERITY_WARNING, $rule);
    }

    public function addInfo(int $row, string $column, string $message, string $rule = ''): self
    {
        return $this->addIssue($row, $column, $message, self::SEVERITY_INFO, $rule);
    }

    public function addIssue(int $row, string $column, string $message, string $severity, string $rule = ''): self
    {
        if (!isset($this->rowErrors[$row])) {
            $this->rowErrors[$row] = [];
        }

        if (!isset($this->rowErrors[$row][$column])) {
            $this->rowErrors[$row][$column] = [];
        }

        $this->rowErrors[$row][$column][] = [
            'message' => $message,
            'severity' => $severity,
            'rule' => $rule,
        ];

        ++$this->counts[$severity];

        if ('' !== $rule) {
            $this->ruleViolations[$rule] = ($this->ruleViolations[$rule] ?? 0) + 1;
        }

        return $this;
    }

    public function setTotalRows(int $total): self
    {
        $this->totalRows = $total;

        return $this;
    }

    public function setValidRows(int $valid): self
    {
        $this->validRows = $valid;

        return $this;
    }

    public function calculateStats(): self
    {
        $rowsWithErrors = [];
        $rowsWithWarnings = [];

        foreach ($this->rowErrors as $row => $columns) {
            foreach ($columns as $column => $issues) {
                foreach ($issues as $issue) {
                    if (self::SEVERITY_ERROR === $issue['severity']) {
                        $rowsWithErrors[$row] = true;
                    } elseif (self::SEVERITY_WARNING === $issue['severity']) {
                        $rowsWithWarnings[$row] = true;
                    }
                }
            }
        }

        $this->invalidRows = count($rowsWithErrors);
        $this->warningRows = count($rowsWithWarnings);
        $this->validRows = $this->totalRows - $this->invalidRows;

        return $this;
    }

    public function finish(): self
    {
        $this->endTime = microtime(true);
        $this->calculateStats();

        return $this;
    }

    public function isValid(): bool
    {
        return 0 === $this->counts['error'];
    }

    public function hasWarnings(): bool
    {
        return $this->counts['warning'] > 0;
    }

    public function getErrorCount(): int
    {
        return $this->counts['error'];
    }

    public function getWarningCount(): int
    {
        return $this->counts['warning'];
    }

    public function getInfoCount(): int
    {
        return $this->counts['info'];
    }

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    public function getValidRows(): int
    {
        return $this->validRows;
    }

    public function getInvalidRows(): int
    {
        return $this->invalidRows;
    }

    /**
     * Get all errors organized by row.
     *
     * @return array<int, array<string, array<array{message: string, severity: string, rule: string}>>>
     */
    public function getRowErrors(): array
    {
        return $this->rowErrors;
    }

    /**
     * Get errors for a specific row.
     *
     * @return array<string, array<array{message: string, severity: string, rule: string}>>
     */
    public function getErrorsForRow(int $row): array
    {
        return $this->rowErrors[$row] ?? [];
    }

    /**
     * Get rows with errors only (excluding warnings/info).
     *
     * @return array<int>
     */
    public function getErrorRowNumbers(): array
    {
        $errorRows = [];

        foreach ($this->rowErrors as $row => $columns) {
            foreach ($columns as $column => $issues) {
                foreach ($issues as $issue) {
                    if (self::SEVERITY_ERROR === $issue['severity']) {
                        $errorRows[] = $row;

                        break 2;
                    }
                }
            }
        }

        return $errorRows;
    }

    /**
     * Get rule violation summary.
     *
     * @return array<string, int>
     */
    public function getRuleViolations(): array
    {
        arsort($this->ruleViolations);

        return $this->ruleViolations;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getSectorCode(): string
    {
        return $this->sectorCode;
    }

    public function getElapsedTime(): float
    {
        $end = $this->endTime ?? microtime(true);

        return $end - $this->startTime;
    }

    /**
     * Merge another report into this one.
     */
    public function merge(self $other): self
    {
        foreach ($other->getRowErrors() as $row => $columns) {
            foreach ($columns as $column => $issues) {
                foreach ($issues as $issue) {
                    $this->addIssue($row, $column, $issue['message'], $issue['severity'], $issue['rule']);
                }
            }
        }

        return $this;
    }

    /**
     * Get summary array for display or JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'filename' => $this->filename,
            'sector' => $this->sectorCode,
            'is_valid' => $this->isValid(),
            'total_rows' => $this->totalRows,
            'valid_rows' => $this->validRows,
            'invalid_rows' => $this->invalidRows,
            'warning_rows' => $this->warningRows,
            'counts' => $this->counts,
            'rule_violations' => $this->ruleViolations,
            'elapsed_seconds' => round($this->getElapsedTime(), 3),
            'errors' => $this->rowErrors,
        ];
    }

    /**
     * Get a condensed summary without full error details.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'filename' => $this->filename,
            'sector' => $this->sectorCode,
            'is_valid' => $this->isValid(),
            'total_rows' => $this->totalRows,
            'valid_rows' => $this->validRows,
            'invalid_rows' => $this->invalidRows,
            'error_count' => $this->counts['error'],
            'warning_count' => $this->counts['warning'],
            'elapsed_seconds' => round($this->getElapsedTime(), 3),
        ];
    }

    /**
     * Format errors for human-readable display.
     *
     * @return array<string>
     */
    public function formatErrors(int $limit = 50): array
    {
        $formatted = [];
        $count = 0;

        foreach ($this->rowErrors as $row => $columns) {
            foreach ($columns as $column => $issues) {
                foreach ($issues as $issue) {
                    if ($count >= $limit) {
                        $formatted[] = sprintf('... and %d more issues', $this->counts['error'] + $this->counts['warning'] - $limit);

                        return $formatted;
                    }

                    $prefix = match ($issue['severity']) {
                        self::SEVERITY_ERROR => 'ERROR',
                        self::SEVERITY_WARNING => 'WARNING',
                        default => 'INFO'
                    };

                    $formatted[] = sprintf('[%s] Row %d, Column "%s": %s', $prefix, $row, $column, $issue['message']);
                    ++$count;
                }
            }
        }

        return $formatted;
    }
}
