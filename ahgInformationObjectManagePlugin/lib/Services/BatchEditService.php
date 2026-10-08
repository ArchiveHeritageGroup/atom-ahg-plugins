<?php

namespace AhgInformationObjectManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Batch edit and batch rename of archival descriptions (#204).
 *
 * The records come from the clipboard. One set of operations - fields to set,
 * access points, a date, a creator, a title pattern - is planned against every
 * record and shown as a preview (field, before, after). Apply recomputes the
 * same plan against fresh data and writes each record through its Propel
 * object and ->save(), exactly as the edit form does, so the nested set, the
 * search index and the audit trails behave as for a normal edit.
 *
 * The planning half (normaliseSlugs, renameTitle, normaliseDate, plan) is pure
 * and is exercised by testing/batch-edit-check.php without a database.
 */
class BatchEditService
{
    /** Most records one batch may touch; every save also reindexes the record. */
    public const MAX_RECORDS = 500;

    /** Access point groups: form key => taxonomy id. */
    public const TERM_GROUPS = ['subject' => 35, 'place' => 42, 'genre' => 78];

    public const LEVEL_TAXONOMY = 34;
    public const EVENT_TYPE_TAXONOMY = 40;
    public const PUBLICATION_STATUS_TAXONOMY = 60;
    public const CREATION_EVENT_ID = 111;
    public const STATUS_TYPE_PUBLICATION_ID = 158;

    // ---------------------------------------------------------------- pure

    /**
     * Clean the posted slug list: strings only, trimmed, unique, slug-shaped.
     *
     * @param mixed $input array of slugs, or a JSON array string
     *
     * @return string[]
     */
    public static function normaliseSlugs($input): array
    {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            $input = is_array($decoded) ? $decoded : preg_split('/[\s,]+/', $input);
        }
        $out = [];
        foreach ((array) $input as $slug) {
            if (!is_string($slug)) {
                continue;
            }
            $slug = trim($slug);
            if ('' !== $slug && strlen($slug) <= 255 && preg_match('/^[^\s\/?#]+$/u', $slug)) {
                $out[$slug] = true;
            }
        }

        return array_keys($out);
    }

    /**
     * Apply a rename rule to one title. Find/replace is plain text (never a
     * regular expression); the prefix and suffix are not added a second time
     * when the title already has them, so running a batch twice is harmless.
     *
     * @param array{find?: string, replace?: string, ci?: bool, prefix?: string, suffix?: string} $rule
     */
    public static function renameTitle(string $title, array $rule): string
    {
        $find = (string) ($rule['find'] ?? '');
        $replace = (string) ($rule['replace'] ?? '');
        $new = $title;

        if ('' !== $find) {
            if (!empty($rule['ci'])) {
                $result = preg_replace('/'.preg_quote($find, '/').'/iu', str_replace(['\\', '$'], ['\\\\', '\\$'], $replace), $new);
                // Invalid UTF-8 makes preg_replace return null: leave the title alone.
                $new = null === $result ? $new : $result;
            } else {
                $new = str_replace($find, $replace, $new);
            }
        }

        $prefix = (string) ($rule['prefix'] ?? '');
        $suffix = (string) ($rule['suffix'] ?? '');
        if ('' !== $prefix && 0 !== strpos($new, $prefix)) {
            $new = $prefix.$new;
        }
        if ('' !== $suffix && (strlen($new) < strlen($suffix) || substr($new, -strlen($suffix)) !== $suffix)) {
            $new .= $suffix;
        }

        return $new;
    }

    /**
     * Normalise a start or end date to a full YYYY-MM-DD for the DATE columns
     * (the server runs MySQL with NO_ZERO_IN_DATE, so "1950-00-00" cannot be
     * stored). Accepts YYYY, YYYY-MM, YYYY-MM-DD, YYYYMM and YYYYMMDD. A partial
     * start date takes the first day, a partial end date the last day. Returns
     * null for empty input and false for anything else.
     *
     * @return null|false|string
     */
    public static function normaliseDate($value, bool $isEnd = false)
    {
        $value = trim((string) $value);
        if ('' === $value) {
            return null;
        }
        if (!preg_match('/^(\d{4})(?:-?(\d{2})(?:-?(\d{2}))?)?$/', $value, $m)) {
            return false;
        }
        $y = (int) $m[1];
        $mo = isset($m[2]) ? (int) $m[2] : ($isEnd ? 12 : 1);
        if ($mo < 1 || $mo > 12) {
            return false;
        }
        $d = isset($m[3]) ? (int) $m[3] : ($isEnd ? (int) date('t', mktime(0, 0, 0, $mo, 1, $y)) : 1);
        if (!checkdate($mo, $d, $y)) {
            return false;
        }

        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
    }

    /** Whether the operations would do anything at all. */
    public static function hasOperations(array $ops): bool
    {
        foreach (['levelId', 'repositoryId', 'pubStatusId', 'accessConditions', 'reproductionConditions', 'languages', 'event', 'rename'] as $k) {
            if (!empty($ops[$k])) {
                return true;
            }
        }
        foreach (self::TERM_GROUPS as $group => $tax) {
            if (!empty($ops['terms'][$group])) {
                return true;
            }
        }

        return !empty($ops['creators']);
    }

    /**
     * The changes the operations make to one record. Fields already holding
     * the new value produce nothing, so an unchanged record has an empty plan.
     *
     * $record: title (?string), levelId, repositoryId, pubStatusId (?int),
     *   accessConditions, reproductionConditions (?string), languages (string[]),
     *   terms (group => [id => name]), creators ([actorId => name]),
     *   events (list of [typeId, actorId, date, start, end]).
     * $ops: as built by parseOperations(); names for terms/creators included.
     * $labels: level, repository, status, language, eventType => [id => name].
     *
     * @return array<int, array{field: string, before: string, after: string, value: mixed}>
     */
    public static function plan(array $record, array $ops, array $labels): array
    {
        $changes = [];
        $name = static function (string $list, $id) use ($labels): string {
            if (null === $id || '' === $id) {
                return '';
            }

            return (string) ($labels[$list][$id] ?? '#'.$id);
        };

        if (!empty($ops['rename'])) {
            $old = $record['title'];
            if (null !== $old && '' !== $old) {
                $new = self::renameTitle($old, $ops['rename']);
                if ($new !== $old) {
                    $changes[] = ['field' => 'title', 'before' => $old, 'after' => $new, 'value' => '' === trim($new) ? false : $new];
                }
            }
        }

        foreach (['levelId' => 'level', 'repositoryId' => 'repository', 'pubStatusId' => 'status'] as $key => $list) {
            if (!empty($ops[$key]) && (int) $ops[$key] !== (int) ($record[$key] ?? 0)) {
                $changes[] = ['field' => $key, 'before' => $name($list, $record[$key] ?? null), 'after' => $name($list, $ops[$key]), 'value' => (int) $ops[$key]];
            }
        }

        foreach (['accessConditions', 'reproductionConditions'] as $key) {
            if (isset($ops[$key]) && '' !== $ops[$key] && $ops[$key] !== (string) ($record[$key] ?? '')) {
                $changes[] = ['field' => $key, 'before' => (string) ($record[$key] ?? ''), 'after' => $ops[$key], 'value' => $ops[$key]];
            }
        }

        if (!empty($ops['languages']['codes'])) {
            $before = array_values((array) ($record['languages'] ?? []));
            $after = 'replace' === ($ops['languages']['mode'] ?? 'add')
                ? array_values(array_unique($ops['languages']['codes']))
                : array_values(array_unique(array_merge($before, $ops['languages']['codes'])));
            if ($after !== $before) {
                $show = static function (array $codes) use ($name): string {
                    return implode('; ', array_map(static function ($c) use ($name) { return $name('language', $c); }, $codes));
                };
                $changes[] = ['field' => 'languages', 'before' => $show($before), 'after' => $show($after), 'value' => $after];
            }
        }

        foreach (self::TERM_GROUPS as $group => $tax) {
            $existing = (array) ($record['terms'][$group] ?? []);
            $add = array_diff_key((array) ($ops['terms'][$group] ?? []), $existing);
            if ($add) {
                $changes[] = ['field' => $group, 'before' => implode('; ', $existing), 'after' => implode('; ', $existing + $add), 'value' => array_keys($add)];
            }
        }

        if (!empty($ops['event'])) {
            $e = $ops['event'];
            $exists = false;
            foreach ((array) ($record['events'] ?? []) as $ev) {
                if ((int) $ev['typeId'] === (int) $e['typeId'] && empty($ev['actorId'])
                    && (string) $ev['date'] === (string) $e['date'] && (string) $ev['start'] === (string) $e['start'] && (string) $ev['end'] === (string) $e['end']) {
                    $exists = true;

                    break;
                }
            }
            if (!$exists) {
                $changes[] = ['field' => 'event', 'before' => '', 'after' => self::describeEvent($e, $name('eventType', $e['typeId'])), 'value' => $e];
            }
        }

        $addCreators = array_diff_key((array) ($ops['creators'] ?? []), (array) ($record['creators'] ?? []));
        if ($addCreators) {
            $existing = (array) ($record['creators'] ?? []);
            $changes[] = ['field' => 'creators', 'before' => implode('; ', $existing), 'after' => implode('; ', $existing + $addCreators), 'value' => array_keys($addCreators)];
        }

        return $changes;
    }

    public static function describeEvent(array $e, string $typeName): string
    {
        $range = trim(($e['start'] ?? '').(($e['end'] ?? '') ? ' - '.$e['end'] : ''));
        $parts = array_filter([$e['date'] ?? '', $range ? '('.$range.')' : '']);

        return $typeName.': '.implode(' ', $parts);
    }

    // ------------------------------------------------------- database reads

    /**
     * Read and validate the posted operations. Names typed for access points
     * and creators must match exactly one existing term or authority record
     * (case-insensitive); nothing is created.
     *
     * @return array{0: array, 1: string[]} operations, errors
     */
    public static function parseOperations(array $post, string $culture, array $labels): array
    {
        $ops = ['terms' => []];
        $errors = [];

        foreach (['levelId' => 'level', 'repositoryId' => 'repository', 'pubStatusId' => 'status'] as $key => $list) {
            $id = (int) ($post[$key] ?? 0);
            if ($id) {
                if (isset($labels[$list][$id])) {
                    $ops[$key] = $id;
                } else {
                    $errors[] = 'Unknown value for '.$key.'.';
                }
            }
        }

        foreach (['accessConditions', 'reproductionConditions'] as $key) {
            $value = trim((string) ($post[$key] ?? ''));
            if ('' !== $value) {
                $ops[$key] = $value;
            }
        }

        $codes = array_values(array_filter((array) ($post['languages'] ?? []), static function ($c) use ($labels) {
            return is_string($c) && isset($labels['language'][$c]);
        }));
        if ($codes) {
            $ops['languages'] = ['mode' => 'replace' === ($post['languagesMode'] ?? '') ? 'replace' : 'add', 'codes' => $codes];
        }

        foreach (self::TERM_GROUPS as $group => $tax) {
            [$found, $missing] = self::resolveTerms(self::lines($post[$group] ?? ''), $tax, $culture);
            $ops['terms'][$group] = $found;
            foreach ($missing as $m) {
                $errors[] = sprintf('No single %s term named "%s".', $group, $m);
            }
        }

        [$ops['creators'], $missing] = self::resolveActors(self::lines($post['creators'] ?? ''), $culture);
        foreach ($missing as $m) {
            $errors[] = sprintf('No single authority record named "%s".', $m);
        }

        $date = trim((string) ($post['eventDate'] ?? ''));
        $start = self::normaliseDate($post['eventStart'] ?? '');
        $end = self::normaliseDate($post['eventEnd'] ?? '', true);
        if ('' !== $date || null !== $start || null !== $end) {
            $typeId = (int) ($post['eventTypeId'] ?? self::CREATION_EVENT_ID);
            if (false === $start || false === $end) {
                $errors[] = 'Start and end dates must be YYYY, YYYY-MM or YYYY-MM-DD.';
            } elseif (!isset($labels['eventType'][$typeId])) {
                $errors[] = 'Unknown event type.';
            } elseif (null !== $start && null !== $end && $start > $end) {
                $errors[] = 'The start date is after the end date.';
            } else {
                $ops['event'] = ['typeId' => $typeId, 'date' => $date, 'start' => (string) $start, 'end' => (string) $end];
            }
        }

        $rename = [
            'find' => (string) ($post['renameFind'] ?? ''),
            'replace' => (string) ($post['renameReplace'] ?? ''),
            'ci' => !empty($post['renameCi']),
            'prefix' => (string) ($post['renamePrefix'] ?? ''),
            'suffix' => (string) ($post['renameSuffix'] ?? ''),
        ];
        if ('' !== $rename['find'] || '' !== $rename['prefix'] || '' !== $rename['suffix']) {
            $ops['rename'] = $rename;
        }

        return [$ops, $errors];
    }

    /** Dropdown values and display names used by the form and the preview. */
    public static function labels(string $culture): array
    {
        $repos = DB::table('repository as r')
            ->join('actor_i18n as a', 'a.id', '=', 'r.id')
            ->whereIn('a.culture', [$culture, 'en'])
            ->orderByRaw('a.culture = ? DESC', [$culture])
            ->get(['r.id', 'a.authorized_form_of_name as name']);
        $repositories = [];
        foreach ($repos as $r) {
            if (!isset($repositories[(int) $r->id]) && null !== $r->name) {
                $repositories[(int) $r->id] = (string) $r->name;
            }
        }
        asort($repositories, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'level' => self::termNames(self::LEVEL_TAXONOMY, $culture),
            'repository' => $repositories,
            'status' => self::termNames(self::PUBLICATION_STATUS_TAXONOMY, $culture),
            'eventType' => self::termNames(self::EVENT_TYPE_TAXONOMY, $culture),
            'language' => \AhgCore\Services\InformationObjectCrudService::getLanguageChoices(),
        ];
    }

    /**
     * Load the records by slug.
     *
     * @return array{0: \QubitInformationObject[], 1: string[]} records keyed by id, slugs not found
     */
    public static function loadRecords(array $slugs): array
    {
        $records = [];
        $missing = [];
        foreach (array_slice($slugs, 0, self::MAX_RECORDS) as $slug) {
            $resource = \QubitObject::getBySlug($slug);
            if ($resource instanceof \QubitInformationObject && \QubitInformationObject::ROOT_ID != $resource->id) {
                $records[(int) $resource->id] = $resource;
            } else {
                $missing[] = $slug;
            }
        }

        return [$records, $missing];
    }

    /** Current values of one record, in the shape plan() reads. */
    public static function snapshot(\QubitInformationObject $resource, string $culture): array
    {
        $id = (int) $resource->id;
        $row = DB::table('information_object')->where('id', $id)->first(['level_of_description_id', 'repository_id']);
        $i18n = DB::table('information_object_i18n')->where('id', $id)->where('culture', $culture)
            ->first(['title', 'access_conditions', 'reproduction_conditions']);
        $status = DB::table('status')->where('object_id', $id)->where('type_id', self::STATUS_TYPE_PUBLICATION_ID)->value('status_id');

        $terms = array_fill_keys(array_keys(self::TERM_GROUPS), []);
        $byTax = array_flip(self::TERM_GROUPS);
        foreach (DB::table('object_term_relation as r')->join('term as t', 't.id', '=', 'r.term_id')
            ->leftJoin('term_i18n as ti', function ($j) use ($culture) {
                $j->on('ti.id', '=', 't.id')->where('ti.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as ts', function ($j) {
                $j->on('ts.id', '=', 't.id')->on('ts.culture', '=', 't.source_culture');
            })
            ->where('r.object_id', $id)->whereIn('t.taxonomy_id', self::TERM_GROUPS)
            ->orderBy('r.id')
            ->get(['t.id', 't.taxonomy_id', DB::raw('COALESCE(ti.name, ts.name) as name')]) as $t) {
            $terms[$byTax[(int) $t->taxonomy_id]][(int) $t->id] = (string) $t->name;
        }

        $events = [];
        $creators = [];
        foreach (DB::table('event as e')
            ->leftJoin('event_i18n as ei', function ($j) use ($culture) {
                $j->on('ei.id', '=', 'e.id')->where('ei.culture', '=', $culture);
            })
            ->leftJoin('actor_i18n as a', function ($j) use ($culture) {
                $j->on('a.id', '=', 'e.actor_id')->where('a.culture', '=', $culture);
            })
            ->where('e.object_id', $id)->orderBy('e.id')
            ->get(['e.type_id', 'e.actor_id', 'e.start_date', 'e.end_date', 'ei.date', 'a.authorized_form_of_name as actor']) as $e) {
            $events[] = ['typeId' => (int) $e->type_id, 'actorId' => $e->actor_id ? (int) $e->actor_id : null, 'date' => (string) $e->date,
                'start' => self::trimDate($e->start_date), 'end' => self::trimDate($e->end_date)];
            if (self::CREATION_EVENT_ID === (int) $e->type_id && $e->actor_id) {
                $creators[(int) $e->actor_id] = (string) ($e->actor ?? '#'.$e->actor_id);
            }
        }

        return [
            'id' => $id,
            'slug' => (string) $resource->slug,
            'title' => $i18n ? $i18n->title : null,
            'levelId' => $row && $row->level_of_description_id ? (int) $row->level_of_description_id : null,
            'repositoryId' => $row && $row->repository_id ? (int) $row->repository_id : null,
            'pubStatusId' => $status ? (int) $status : null,
            'accessConditions' => $i18n ? $i18n->access_conditions : null,
            'reproductionConditions' => $i18n ? $i18n->reproduction_conditions : null,
            'languages' => array_values((array) $resource->language),
            'terms' => $terms,
            'creators' => $creators,
            'events' => $events,
        ];
    }

    /**
     * Plan every record and note the ones that will be skipped.
     *
     * @return array<int, array{id: int, slug: string, title: string, changes: array, skip: ?string}>
     */
    public static function preview(array $records, array $ops, array $labels, string $culture): array
    {
        $rows = [];
        foreach ($records as $id => $resource) {
            $snap = self::snapshot($resource, $culture);
            $changes = self::plan($snap, $ops, $labels);
            $rows[$id] = [
                'id' => $id,
                'slug' => $snap['slug'],
                'title' => (string) ($snap['title'] ?? $resource->getTitle(['cultureFallback' => true])),
                'changes' => $changes,
                'skip' => self::skipReason($resource, $changes, $ops),
            ];
        }

        return $rows;
    }

    // ------------------------------------------------------------- writes

    /**
     * Apply the operations. Every record is planned again against its current
     * values, then saved through Propel in its own transaction.
     *
     * @return array<int, array{id: int, slug: string, title: string, status: string, message: string, changes: array}>
     */
    public static function apply(array $records, array $ops, array $labels, string $culture, string $batchId): array
    {
        $results = [];
        foreach ($records as $id => $resource) {
            $snap = self::snapshot($resource, $culture);
            $changes = self::plan($snap, $ops, $labels);
            $title = (string) ($snap['title'] ?? $resource->getTitle(['cultureFallback' => true]));
            $result = ['id' => $id, 'slug' => $snap['slug'], 'title' => $title, 'changes' => $changes, 'status' => 'unchanged', 'message' => ''];

            if (null !== $skip = self::skipReason($resource, $changes, $ops)) {
                $result['status'] = 'skipped';
                $result['message'] = $skip;
                $results[$id] = $result;

                continue;
            }
            if (!$changes) {
                $results[$id] = $result;

                continue;
            }

            $conn = \Propel::getConnection();
            $conn->beginTransaction();

            try {
                foreach ($changes as $change) {
                    self::applyChange($resource, $change, $culture);
                }
                $resource->save();
                $conn->commit();
                $result['status'] = 'updated';
                self::audit($resource, $snap, $changes, $batchId, count($records));
            } catch (\Throwable $e) {
                $conn->rollBack();
                $result['status'] = 'error';
                $result['message'] = $e->getMessage();
            }
            $results[$id] = $result;
        }

        return $results;
    }

    private static function applyChange(\QubitInformationObject $resource, array $change, string $culture): void
    {
        switch ($change['field']) {
            case 'title':
                $resource->__set('title', $change['value'], ['culture' => $culture]);

                break;

            case 'levelId':
                $resource->levelOfDescriptionId = $change['value'];

                break;

            case 'repositoryId':
                $resource->repositoryId = $change['value'];

                break;

            case 'pubStatusId':
                $resource->setPublicationStatus($change['value']);

                break;

            case 'accessConditions':
            case 'reproductionConditions':
                $resource->__set($change['field'], $change['value'], ['culture' => $culture]);

                break;

            case 'languages':
                $resource->language = $change['value'];

                break;

            case 'subject':
            case 'place':
            case 'genre':
                foreach ($change['value'] as $termId) {
                    $relation = new \QubitObjectTermRelation();
                    $relation->termId = $termId;
                    $resource->objectTermRelationsRelatedByobjectId[] = $relation;
                }

                break;

            case 'event':
                $event = new \QubitEvent();
                $event->typeId = $change['value']['typeId'];
                $event->startDate = '' === $change['value']['start'] ? null : $change['value']['start'];
                $event->endDate = '' === $change['value']['end'] ? null : $change['value']['end'];
                if ('' !== $change['value']['date']) {
                    $event->__set('date', $change['value']['date'], ['culture' => $culture]);
                }
                $resource->eventsRelatedByobjectId[] = $event;

                break;

            case 'creators':
                foreach ($change['value'] as $actorId) {
                    $event = new \QubitEvent();
                    $event->typeId = self::CREATION_EVENT_ID;
                    $event->actorId = $actorId;
                    $resource->eventsRelatedByobjectId[] = $event;
                }

                break;
        }
    }

    /** Why a record will not be written, or null when it will. */
    private static function skipReason(\QubitInformationObject $resource, array $changes, array $ops): ?string
    {
        if (!$changes) {
            return null;
        }
        if (!\AtomExtensions\Services\AclService::check($resource, 'update')) {
            return 'You may not update this description.';
        }
        foreach ($changes as $change) {
            if ('pubStatusId' === $change['field'] && !\AtomExtensions\Services\AclService::check($resource, 'publish')) {
                return 'You may not change the publication status of this description.';
            }
            if ('title' === $change['field'] && false === $change['value']) {
                return 'The new title would be empty.';
            }
        }

        return null;
    }

    /** One audit row per updated record, with the before and after values. */
    private static function audit(\QubitInformationObject $resource, array $snap, array $changes, string $batchId, int $count): void
    {
        if (!class_exists('AhgAuditTrail\\Services\\AhgAuditService')) {
            return;
        }

        try {
            $old = $new = [];
            foreach ($changes as $c) {
                $old[$c['field']] = $c['before'];
                $new[$c['field']] = $c['after'];
            }
            $user = \sfContext::hasInstance() ? \sfContext::getInstance()->getUser() : null;
            \AhgAuditTrail\Services\AhgAuditService::logAction('update', 'QubitInformationObject', (int) $resource->id, [
                'user_id' => $user && $user->isAuthenticated() ? $user->getAttribute('user_id') : null,
                'username' => $user && $user->isAuthenticated() ? ($user->getAttribute('username') ?? $user->getUsername()) : null,
                'slug' => $snap['slug'],
                'title' => (string) ($snap['title'] ?? ''),
                'module' => 'batchEdit',
                'action_name' => 'batch',
                'old_values' => $old,
                'new_values' => $new,
                'changed_fields' => array_keys($new),
                'metadata' => ['batch_id' => $batchId, 'batch_size' => $count],
            ]);
        } catch (\Throwable $e) {
            error_log('Batch edit audit error: '.$e->getMessage());
        }
    }

    // ------------------------------------------------------------ helpers

    /** @return string[] non-empty trimmed lines */
    public static function lines($text): array
    {
        $out = [];
        foreach (preg_split('/\R/u', (string) $text) ?: [] as $line) {
            $line = trim($line);
            if ('' !== $line) {
                $out[mb_strtolower($line)] = $line;
            }
        }

        return array_values($out);
    }

    private static function trimDate($date): string
    {
        return null === $date ? '' : (string) $date;
    }

    private static function termNames(int $taxonomyId, string $culture): array
    {
        $out = [];
        foreach (DB::table('term as t')
            ->leftJoin('term_i18n as ti', function ($j) use ($culture) {
                $j->on('ti.id', '=', 't.id')->where('ti.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as ts', function ($j) {
                $j->on('ts.id', '=', 't.id')->on('ts.culture', '=', 't.source_culture');
            })
            ->where('t.taxonomy_id', $taxonomyId)
            ->orderBy('t.lft')
            ->get(['t.id', DB::raw('COALESCE(ti.name, ts.name) as name')]) as $t) {
            $out[(int) $t->id] = (string) $t->name;
        }

        return $out;
    }

    /** @return array{0: array<int, string>, 1: string[]} found [id => name], names not resolved */
    private static function resolveTerms(array $names, int $taxonomyId, string $culture): array
    {
        $found = $missing = [];
        foreach ($names as $n) {
            $ids = DB::table('term as t')->join('term_i18n as ti', 'ti.id', '=', 't.id')
                ->where('t.taxonomy_id', $taxonomyId)->whereIn('ti.culture', [$culture, 'en'])
                ->whereRaw('LOWER(ti.name) = ?', [mb_strtolower($n)])
                ->distinct()->pluck('t.id')->all();
            if (1 === count($ids)) {
                $found[(int) $ids[0]] = $n;
            } else {
                $missing[] = $n;
            }
        }

        return [$found, $missing];
    }

    /** Authority records only (class QubitActor), never repositories, donors or users. */
    private static function resolveActors(array $names, string $culture): array
    {
        $found = $missing = [];
        foreach ($names as $n) {
            $ids = DB::table('actor_i18n as a')->join('object as o', 'o.id', '=', 'a.id')
                ->where('o.class_name', 'QubitActor')->whereIn('a.culture', [$culture, 'en'])
                ->whereRaw('LOWER(a.authorized_form_of_name) = ?', [mb_strtolower($n)])
                ->distinct()->pluck('a.id')->all();
            if (1 === count($ids)) {
                $found[(int) $ids[0]] = $n;
            } else {
                $missing[] = $n;
            }
        }

        return [$found, $missing];
    }
}
