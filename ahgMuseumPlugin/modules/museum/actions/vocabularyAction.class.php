<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Returns existing vocabulary values for CCO fields.
 * Combines local database values with Getty vocabulary search.
 */
class museumVocabularyAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $field = $request->getParameter('field', '');
        $query = trim($request->getParameter('query', ''));

        $results = [];

        // Get existing values from ccoData JSON
        $existing = $this->getExistingValues($field);

        // Filter by query if provided
        if (!empty($query)) {
            $existing = array_filter($existing, function($item) use ($query) {
                return stripos($item['label'], $query) !== false;
            });
        }

        // Add existing values with "local" source
        foreach ($existing as $item) {
            $results[] = [
                'id' => $item['label'],
                'label' => $item['label'],
                'source' => 'local',
                'count' => $item['count'] ?? 1,
            ];
        }

        return $this->renderText(json_encode([
            'success' => true,
            'field' => $field,
            'results' => array_values($results),
        ], JSON_PRETTY_PRINT));
    }

    /**
     * Get existing values for a CCO field from the database.
     */
    private function getExistingValues(string $field): array
    {
        $values = [];

        // Query ccoData JSON from property + property_i18n tables
        $rows = DB::table('property as p')
            ->join('property_i18n as pi', 'p.id', '=', 'pi.id')
            ->where('p.name', 'ccoData')
            ->whereNotNull('pi.value')
            ->where('pi.culture', 'en')
            ->pluck('pi.value');

        foreach ($rows as $json) {
            $data = json_decode($json, true);
            if (!$data || !isset($data[$field])) {
                continue;
            }

            $value = trim($data[$field]);
            if (empty($value)) {
                continue;
            }

            // Handle comma-separated values
            $parts = array_map('trim', explode(',', $value));
            foreach ($parts as $part) {
                if (empty($part)) {
                    continue;
                }
                if (!isset($values[$part])) {
                    $values[$part] = ['label' => $part, 'count' => 0];
                }
                $values[$part]['count']++;
            }
        }

        // Sort by count descending
        uasort($values, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        return array_values($values);
    }
}
