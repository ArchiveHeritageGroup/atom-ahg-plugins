<?php

/**
 * PrometheusTextRenderer - render collected metric families to the Prometheus
 * text exposition format (version 0.0.4), the encoding a Prometheus server
 * expects when scraping a /metrics endpoint.
 *
 * Produces, per family:
 *   # HELP <ns>_<name> <help>
 *   # TYPE <ns>_<name> <type>
 *   <ns>_<name>{label="value",...} <value>
 *
 * Histograms additionally emit _bucket{le="..."}, _sum and _count series.
 * Label values are escaped per the spec (backslash, double-quote, newline).
 *
 * @author The Archive and Heritage Group (Pty) Ltd
 * @license GPL-3.0
 */

namespace AtomExtensions\Observability;

class PrometheusTextRenderer
{
    public const MIME_TYPE = 'text/plain; version=0.0.4; charset=utf-8';

    /**
     * @param array<int,array<string,mixed>> $families As returned by a storage adapter's collect()
     */
    public function render(string $namespace, array $families): string
    {
        $out = [];
        foreach ($families as $family) {
            $metric = $namespace.'_'.$family['name'];
            $out[] = '# HELP '.$metric.' '.$this->escapeHelp((string) $family['help']);
            $out[] = '# TYPE '.$metric.' '.$family['type'];

            $declaredLabels = $family['labels'] ?? [];

            foreach ($family['samples'] as $sample) {
                $suffix = $sample['suffix'] ?? '';
                $labels = $this->zipLabels($declaredLabels, $sample['labels'] ?? []);

                if (isset($sample['le'])) {
                    $labels['le'] = $sample['le'];
                }

                $line = $metric.$suffix.$this->renderLabels($labels).' '.$this->formatValue($sample['value']);
                $out[] = $line;
            }
        }

        return implode("\n", $out)."\n";
    }

    /**
     * Combine declared label names with this sample's label values.
     *
     * @param array<int,string> $names
     * @param array<int,string> $values
     * @return array<string,string>
     */
    private function zipLabels(array $names, array $values): array
    {
        $labels = [];
        foreach ($names as $i => $name) {
            $labels[$name] = (string) ($values[$i] ?? '');
        }

        return $labels;
    }

    /**
     * @param array<string,string> $labels
     */
    private function renderLabels(array $labels): string
    {
        if (empty($labels)) {
            return '';
        }
        $parts = [];
        foreach ($labels as $k => $v) {
            $parts[] = $k.'="'.$this->escapeLabelValue($v).'"';
        }

        return '{'.implode(',', $parts).'}';
    }

    private function escapeLabelValue(string $v): string
    {
        return str_replace(['\\', "\n", '"'], ['\\\\', '\\n', '\\"'], $v);
    }

    private function escapeHelp(string $v): string
    {
        return str_replace(['\\', "\n"], ['\\\\', '\\n'], $v);
    }

    private function formatValue($value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }
        $f = (float) $value;
        if ($f === floor($f) && abs($f) < 1e15) {
            return (string) (int) $f;
        }

        return rtrim(rtrim(sprintf('%.6f', $f), '0'), '.');
    }
}
