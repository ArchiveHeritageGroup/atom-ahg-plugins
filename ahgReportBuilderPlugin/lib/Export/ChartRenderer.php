<?php

/**
 * Chart Renderer for Report Builder.
 *
 * Provides server-side chart rendering capabilities for export embedding.
 * Uses PhpWord's built-in Chart element for Word documents.
 */
class ChartRenderer
{
    /**
     * Add a chart to a PhpWord section.
     *
     * @param \PhpOffice\PhpWord\Element\Section $section   The PhpWord section
     * @param array                               $chartData The chart data (labels, data, type)
     * @param array                               $options   Chart options
     */
    public static function addToWordSection($section, array $chartData, array $options = []): void
    {
        if (!class_exists('PhpOffice\PhpWord\PhpWord')) {
            $section->addText('[Chart: data visualization]', ['italic' => true, 'color' => '888888']);

            return;
        }

        $labels = $chartData['labels'] ?? [];
        $data = $chartData['data'] ?? [];
        $chartType = $options['type'] ?? 'bar';

        if (empty($labels) || empty($data)) {
            $section->addText('[No chart data available]', ['italic' => true, 'color' => '888888']);

            return;
        }

        // Map chart types to PhpWord chart types
        $wordChartType = self::mapChartType($chartType);

        $categories = $labels;
        $series = [
            [
                'name' => $options['label'] ?? 'Data',
                'data' => array_map('floatval', $data),
            ],
        ];

        $chartStyle = [
            'width' => \PhpOffice\PhpWord\Shared\Converter::cmToEmu($options['width'] ?? 15),
            'height' => \PhpOffice\PhpWord\Shared\Converter::cmToEmu($options['height'] ?? 8),
            'showLegend' => $options['showLegend'] ?? false,
        ];

        try {
            $chart = $section->addChart($wordChartType, $categories, $series[0]['data'], $chartStyle);

            if (isset($options['title'])) {
                $chart->getStyle()->setTitle($options['title']);
            }
        } catch (\Exception $e) {
            $section->addText(
                '[Chart rendering error: ' . $e->getMessage() . ']',
                ['italic' => true, 'color' => '888888']
            );
        }
    }

    /**
     * Generate chart data for a report section.
     *
     * @param object $section    The report section
     * @param array  $reportData The report data
     *
     * @return array Chart data (labels, data, type)
     */
    public static function getChartDataFromSection(object $section, array $reportData): array
    {
        $config = is_array($section->config) ? $section->config : [];

        $groupBy = $config['groupBy'] ?? null;
        $chartType = $config['chartType'] ?? 'bar';
        $aggregate = $config['aggregate'] ?? 'count';

        if (!$groupBy || empty($reportData['results'])) {
            return [
                'labels' => ['Total'],
                'data' => [$reportData['total'] ?? 0],
                'type' => $chartType,
            ];
        }

        // Group and aggregate data
        $grouped = [];
        foreach ($reportData['results'] as $row) {
            $key = $row->{$groupBy} ?? 'Unknown';
            if (empty($key)) {
                $key = 'Unknown';
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = 0;
            }
            $grouped[$key]++;
        }

        // Sort by value descending, limit to 15
        arsort($grouped);
        $grouped = array_slice($grouped, 0, 15, true);

        return [
            'labels' => array_keys($grouped),
            'data' => array_values($grouped),
            'type' => $chartType,
        ];
    }

    /**
     * Map frontend chart type names to PhpWord chart types.
     *
     * @param string $type The frontend chart type
     *
     * @return string The PhpWord chart type
     */
    private static function mapChartType(string $type): string
    {
        $map = [
            'bar' => 'bar',
            'horizontalBar' => 'bar',
            'line' => 'line',
            'pie' => 'pie',
            'doughnut' => 'doughnut',
            'area' => 'area',
            'scatter' => 'scatter',
            'radar' => 'radar',
        ];

        return $map[$type] ?? 'bar';
    }

    /**
     * Generate an HTML chart representation for PDF export.
     *
     * @param array $chartData The chart data
     * @param array $options   Chart options
     *
     * @return string HTML for the chart
     */
    public static function toHtml(array $chartData, array $options = []): string
    {
        $labels = $chartData['labels'] ?? [];
        $data = $chartData['data'] ?? [];

        if (empty($labels) || empty($data)) {
            return '<div style="text-align:center;color:#888;padding:20px;"><em>No chart data</em></div>';
        }

        $maxVal = max($data) ?: 1;
        $title = $options['title'] ?? '';

        $html = '<div style="margin:15px 0;">';
        if ($title) {
            $html .= '<div style="font-weight:bold;margin-bottom:10px;">' . htmlspecialchars($title) . '</div>';
        }

        // Simple horizontal bar chart in HTML/CSS
        $html .= '<table style="width:100%;border-collapse:collapse;">';
        foreach ($labels as $i => $label) {
            $value = $data[$i] ?? 0;
            $pct = ($value / $maxVal) * 100;

            $html .= '<tr>';
            $html .= '<td style="width:30%;padding:3px 8px 3px 0;font-size:9px;text-align:right;white-space:nowrap;">';
            $html .= htmlspecialchars(strlen($label) > 25 ? substr($label, 0, 25) . '...' : $label);
            $html .= '</td>';
            $html .= '<td style="padding:3px 0;">';
            $html .= '<div style="background:#0d6efd;height:16px;width:' . $pct . '%;border-radius:2px;"></div>';
            $html .= '</td>';
            $html .= '<td style="width:50px;padding:3px 0 3px 8px;font-size:9px;font-weight:bold;">';
            $html .= number_format($value);
            $html .= '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }
}
