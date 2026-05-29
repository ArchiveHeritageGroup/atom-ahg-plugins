<?php

declare(strict_types=1);

/**
 * OpenUrlResolver (#110)
 *
 * Resolves an inbound NISO OpenURL 1.0 (Z39.88-2004) ContextObject to matching
 * catalogue records. Accepts the standard rft.* keys (rft.isbn, rft.issn,
 * rft.title/btitle/jtitle, rft.atitle, rft.au, rft.date) and their bare
 * equivalents, matching against library_item → published information objects.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */
class OpenUrlResolver
{
    /**
     * Resolve an OpenURL parameter set to candidate records.
     *
     * @param array $params raw request parameters
     * @return array list of {id, title, slug, isbn, issn, publisher, publication_date}
     */
    public function resolve(array $params): array
    {
        $isbn  = $this->clean($this->param($params, ['rft.isbn', 'isbn']), true);
        $issn  = $this->clean($this->param($params, ['rft.issn', 'issn']));
        $title = trim((string) $this->param($params, ['rft.title', 'rft.btitle', 'rft.jtitle', 'title']));

        if ($isbn === '' && $issn === '' && $title === '') {
            return [];
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $q = $db::table('library_item as li')
            ->join('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->join('status as pub_st', function ($j) {
                $j->on('io.id', '=', 'pub_st.object_id')->where('pub_st.type_id', '=', 158);
            })
            ->where('pub_st.status_id', 160);

        // Most-specific identifier wins; title is a fallback.
        if ($isbn !== '') {
            $q->where('li.isbn', $isbn);
        } elseif ($issn !== '') {
            $q->where('li.issn', $issn);
        } else {
            $q->where('ioi.title', 'LIKE', '%' . $title . '%');
        }

        return $q->orderBy('ioi.title')->limit(50)
            ->get(['li.id', 'ioi.title', 'slug.slug', 'li.isbn', 'li.issn', 'li.publisher', 'li.publication_date'])
            ->all();
    }

    private function param(array $params, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (isset($params[$k]) && $params[$k] !== '') {
                return (string) $params[$k];
            }
        }

        return null;
    }

    private function clean(?string $v, bool $isbn = false): string
    {
        if ($v === null) {
            return '';
        }
        $v = trim($v);

        return $isbn ? preg_replace('/[^0-9Xx]/', '', $v) : preg_replace('/[^0-9Xx\-]/', '', $v);
    }
}
