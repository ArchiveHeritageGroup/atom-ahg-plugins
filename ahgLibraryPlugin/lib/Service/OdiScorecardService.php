<?php

declare(strict_types=1);

/**
 * OdiScorecardService (#110)
 *
 * NISO Open Discovery Initiative (ODI) style metadata-quality scorecard for the
 * library catalogue: measures the fill rate of the discovery-critical fields
 * across published library items and produces an overall completeness score.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */
class OdiScorecardService
{
    /**
     * Compute the scorecard.
     *
     * @return array{total:int, fields:array<string,array{label:string,filled:int,pct:float}>, score:float}
     */
    public function score(): array
    {
        $db = \Illuminate\Database\Capsule\Manager::class;

        // Published library items = the discovery universe.
        $base = fn () => $db::table('library_item as li')
            ->join('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->join('status as pub_st', function ($j) {
                $j->on('io.id', '=', 'pub_st.object_id')->where('pub_st.type_id', '=', 158);
            })
            ->where('pub_st.status_id', 160);

        $total = $base()->count();

        if ($total === 0) {
            return ['total' => 0, 'fields' => [], 'score' => 0.0];
        }

        $fields = [];

        // Simple column-presence fields on library_item.
        $colFields = [
            'title'       => ['label' => 'Title', 'expr' => null],   // via i18n, handled below
            'publisher'   => ['label' => 'Publisher', 'col' => 'publisher'],
            'date'        => ['label' => 'Publication date', 'col' => 'publication_date'],
            'call_number' => ['label' => 'Call number', 'col' => 'call_number'],
        ];

        // Title via i18n.
        $titleFilled = $base()
            ->join('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->whereNotNull('ioi.title')->where('ioi.title', '!=', '')->count();
        $fields['title'] = ['label' => 'Title', 'filled' => $titleFilled, 'pct' => $this->pct($titleFilled, $total)];

        foreach (['publisher' => 'Publisher', 'publication_date' => 'Publication date', 'call_number' => 'Call number'] as $col => $label) {
            $filled = $base()->whereNotNull('li.' . $col)->where('li.' . $col, '!=', '')->count();
            $fields[$col] = ['label' => $label, 'filled' => $filled, 'pct' => $this->pct($filled, $total)];
        }

        // Identifier (ISBN or ISSN present).
        $idFilled = $base()->where(function ($w) {
            $w->where('isbn', '!=', '')->whereNotNull('isbn')
              ->orWhere(function ($w2) { $w2->where('issn', '!=', '')->whereNotNull('issn'); });
        })->count();
        $fields['identifier'] = ['label' => 'Standard identifier', 'filled' => $idFilled, 'pct' => $this->pct($idFilled, $total)];

        // Creator + subject via the library link tables (distinct items with ≥1 row).
        $creatorFilled = $db::table('library_item_creator')->distinct()->count('library_item_id');
        $fields['creator'] = ['label' => 'Creator', 'filled' => $creatorFilled, 'pct' => $this->pct($creatorFilled, $total)];

        $subjectFilled = $db::table('library_item_subject')->distinct()->count('library_item_id');
        $fields['subject'] = ['label' => 'Subject', 'filled' => $subjectFilled, 'pct' => $this->pct($subjectFilled, $total)];

        $score = count($fields) ? round(array_sum(array_column($fields, 'pct')) / count($fields), 1) : 0.0;

        return ['total' => $total, 'fields' => $fields, 'score' => $score];
    }

    private function pct(int $filled, int $total): float
    {
        return $total > 0 ? round(($filled / $total) * 100, 1) : 0.0;
    }
}
