<?php

namespace AhgChatbotPlugin\Services;

use AtomExtensions\Services\AhgSettingsService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Answers the questions visitors ask about the institution rather than the
 * records: when can I visit, what are your opening times, who can I contact,
 * is there wheelchair access, can I get copies.
 *
 * Sources are the install's own public data, nothing else:
 *   - repository records (ISDIAH): opening times, access conditions, disabled
 *     access, research and reproduction services, public facilities, and the
 *     REPOSITORY's contact details, primary contact first;
 *   - the static pages named in chatbot_info_pages (default contact, about,
 *     accessibility).
 *
 * Contact details of donors, rights holders, authority records and users are
 * never read: they are private people, not the institution.
 */
class ChatbotSiteInfo
{
    /** Words that mark a question about visiting or contacting the institution. */
    private const PATTERN = '/\b(visit|visiting|open|opening|hours|times?|closed|holiday|weekend|contact|phone|telephone|call|e-?mail|address|located|location|where are you|directions|parking|wheelchair|disabled|disability|accessib\w*|reading room|research(er)? (room|services)|appointment|book(ing)?|copies|copy|reproduction|scan(ning)?|fees?|cost|price|primary|who (can|do|should) i|staff|archivist|librarian|curator|facilities)\b/i';

    private const MAX_CHARS = 3500;

    public static function isAbout(string $question): bool
    {
        return 1 === preg_match(self::PATTERN, $question);
    }

    /**
     * @param int|null $pageObjectId the record being viewed; its repository leads
     *
     * @return array{text:string,sources:array<int,array>}
     */
    public static function forQuestion(string $culture, ?int $pageObjectId = null): array
    {
        $text = '';
        $sources = [];

        try {
            $leadRepo = $pageObjectId ? (int) DB::table('information_object')->where('id', $pageObjectId)->value('repository_id') : 0;

            foreach (self::repositories($culture, $leadRepo) as $repo) {
                $block = self::repositoryBlock($repo, $culture);
                if ('' === $block || strlen($text) + strlen($block) > self::MAX_CHARS) {
                    continue;
                }
                $text .= $block;
                $sources[] = ['slug' => $repo->slug, 'title' => $repo->name, 'identifier' => null, 'thumbnail' => null, 'module' => 'repository'];
            }

            $pages = array_filter(array_map('trim', explode(',', (string) AhgSettingsService::get('chatbot_info_pages', 'contact,about,accessibility'))));
            foreach ($pages as $slug) {
                $page = DB::table('slug as s')
                    ->join('static_page_i18n as p', 'p.id', '=', 's.object_id')
                    ->where('s.slug', $slug)->where('p.culture', $culture)
                    ->first(['s.slug', 'p.title', 'p.content']);
                $body = $page ? trim(preg_replace('/\s+/', ' ', strip_tags((string) $page->content))) : '';
                if ('' === $body) {
                    continue;
                }
                $block = '--- Page: '.$page->title." ---\n".mb_substr($body, 0, 1200)."\n\n";
                if (strlen($text) + strlen($block) > self::MAX_CHARS) {
                    break;
                }
                $text .= $block;
                $sources[] = ['slug' => $page->slug, 'title' => $page->title, 'identifier' => null, 'thumbnail' => null, 'module' => 'staticpage'];
            }
        } catch (\Throwable $e) {
            error_log('chatbot.site_info_failed: '.$e->getMessage());
        }

        return ['text' => $text, 'sources' => $sources];
    }

    /** Repositories that have something a visitor could use; the record's own first. */
    private static function repositories(string $culture, int $leadRepo): array
    {
        $rows = DB::table('repository as r')
            ->join('actor_i18n as a', static fn ($j) => $j->on('a.id', '=', 'r.id')->where('a.culture', $culture))
            ->join('slug as s', 's.object_id', '=', 'r.id')
            ->leftJoin('repository_i18n as ri', static fn ($j) => $j->on('ri.id', '=', 'r.id')->where('ri.culture', $culture))
            ->get(['r.id', 's.slug', 'a.authorized_form_of_name as name', 'ri.opening_times', 'ri.access_conditions', 'ri.disabled_access',
                'ri.research_services', 'ri.reproduction_services', 'ri.public_facilities'])
            ->all();

        usort($rows, static fn ($a, $b) => ((int) ($b->id === $leadRepo)) <=> ((int) ($a->id === $leadRepo)));

        return $rows;
    }

    private static function repositoryBlock(object $repo, string $culture): string
    {
        $lines = [];
        foreach ([
            'opening_times' => 'Opening times',
            'access_conditions' => 'Access conditions',
            'disabled_access' => 'Disabled access',
            'research_services' => 'Research services',
            'reproduction_services' => 'Reproduction services',
            'public_facilities' => 'Public facilities',
        ] as $field => $label) {
            $value = trim(strip_tags((string) $repo->{$field}));
            if ('' !== $value) {
                $lines[] = $label.': '.mb_substr($value, 0, 400);
            }
        }

        // The repository's own contacts only, primary first.
        $contacts = DB::table('contact_information as c')
            ->leftJoin('contact_information_i18n as ci', static fn ($j) => $j->on('ci.id', '=', 'c.id')->where('ci.culture', $culture))
            ->where('c.actor_id', $repo->id)
            ->orderByDesc('c.primary_contact')
            ->get(['c.primary_contact', 'c.contact_person', 'c.email', 'c.telephone', 'c.website', 'c.street_address', 'ci.city', 'c.postal_code', 'c.country_code'])
            ->all();
        foreach ($contacts as $c) {
            $parts = array_filter([
                $c->contact_person ? 'Contact person: '.$c->contact_person : null,
                $c->email ? 'E-mail: '.$c->email : null,
                $c->telephone ? 'Telephone: '.$c->telephone : null,
                $c->website ? 'Website: '.$c->website : null,
                trim(implode(', ', array_filter([$c->street_address, $c->city, $c->postal_code, $c->country_code]))) ?: null,
            ]);
            if ($parts) {
                $lines[] = ($c->primary_contact ? 'Primary contact - ' : 'Contact - ').implode('; ', $parts);
            }
        }

        return $lines ? '--- Institution: '.$repo->name." ---\n".implode("\n", $lines)."\n\n" : '';
    }
}
