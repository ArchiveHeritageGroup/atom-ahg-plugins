<?php

/**
 * Job creation and draining for ahgSubtreeExportPlugin. AtoM 2.8.1, QubitPdo only.
 *
 * A job is created once with its subtree bounds fixed, then drained: in a single
 * pass (batch_size 0) or in sequential batches that resume from resume_after_lft.
 */
class SubtreeExportRunner
{
    /**
     * Empty a previous export from the destination, so a re-run starts clean.
     *
     * ⚠️ Removes ONLY what this task creates: objects/, metadata/, manifest.json,
     * manifest.js and lookup.html. It deliberately does NOT recursively delete the
     * --output directory itself. An export tool with an unbounded rm -rf behind a
     * path argument is one mistyped --output away from eating a drive, and the
     * destination here is external storage that may hold other things.
     *
     * @return bool false when something could not be removed, so the caller can
     *              stop rather than export on top of a half-cleared package
     */
    public static function clean(string $path, ?callable $log = null): bool
    {
        // rtrim turns "/" into "", which previously hit the early return below and
        // reported success without the guard ever running. Keep root as "/" so it
        // reaches safeToClean and is refused there.
        $root = '/' === $path ? '/' : rtrim($path, '/');

        if ('' === $root) {
            return false;
        }

        // Guard BEFORE the is_dir check: a refusal must be reported as a refusal,
        // never as "nothing to do".
        if (!self::safeToClean($root, $log)) {
            return false;
        }

        if (!is_dir($root)) {
            return true;
        }

        $ok = true;

        foreach (['objects', 'metadata'] as $dir) {
            $target = $root.'/'.$dir;

            if (is_dir($target) && !self::removeTree($target)) {
                $ok = false;
                if ($log) {
                    $log('could not remove '.$target);
                }
            }
        }

        foreach (['manifest.json', 'manifest.js', 'lookup.html'] as $file) {
            $target = $root.'/'.$file;

            if (is_file($target) && !@unlink($target)) {
                $ok = false;
                if ($log) {
                    $log('could not remove '.$target);
                }
            }
        }

        if ($ok && $log) {
            $log('cleared previous export in '.$root);
        }

        return $ok;
    }

    /**
     * Refuse to clean anywhere that could destroy the installation or the system.
     *
     * ⚠️ The destination is operator-supplied on the command line. A typo such as
     * --output=/usr/share/nginx/atom would otherwise delete objects/ and metadata/
     * out of a live AtoM tree, and --output=/ would try the whole machine.
     *
     * Four refusals, each for a different way of getting it wrong:
     *  - the target IS the AtoM root
     *  - the target is INSIDE the AtoM root (so /usr/share/nginx/atom/uploads too)
     *  - the target CONTAINS the AtoM root (so /usr/share/nginx, or /)
     *  - the target is a shallow system path with fewer than two levels
     *
     * Uses strpos rather than str_starts_with: this must run on PHP 7.4, which is
     * what the AtoM 2.8 host it was written for has.
     */
    private static function safeToClean(string $root, ?callable $log = null): bool
    {
        $real = realpath($root);

        if (false === $real) {
            return true;
        }

        // Same trap as above: rtrim would reduce "/" to "" and slip past every check.
        $real = '/' === $real ? '/' : rtrim($real, '/');
        $atomRoot = realpath((string) sfConfig::get('sf_root_dir'));
        $atomRoot = false === $atomRoot ? '' : rtrim($atomRoot, '/');

        $refuse = function ($why) use ($log, $real) {
            if ($log) {
                $log('REFUSING to clean '.$real.': '.$why);
            }

            return false;
        };

        if ('' !== $atomRoot) {
            if ($real === $atomRoot) {
                return $refuse('that is the AtoM root.');
            }

            if (0 === strpos($real.'/', $atomRoot.'/')) {
                return $refuse('that is inside the AtoM root.');
            }

            if (0 === strpos($atomRoot.'/', $real.'/')) {
                return $refuse('that contains the AtoM root.');
            }
        }

        $forbidden = [
            '/', '/bin', '/boot', '/data', '/dev', '/etc', '/home', '/lib', '/media',
            '/mnt', '/opt', '/proc', '/root', '/run', '/sbin', '/srv', '/sys', '/tmp',
            '/usr', '/usr/local', '/usr/share', '/usr/share/nginx', '/var', '/var/www',
        ];

        if (in_array($real, $forbidden, true)) {
            return $refuse('that is a system directory.');
        }

        // Fewer than two levels deep is almost always a typo, never a chosen
        // export destination.
        if ('/' === $real || substr_count(trim($real, '/'), '/') < 1) {
            return $refuse('that is too close to the filesystem root.');
        }

        return true;
    }

    /** Depth-first removal, files before their directory. */
    private static function removeTree(string $dir): bool
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $done = $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());

            if (!$done) {
                return false;
            }
        }

        return @rmdir($dir);
    }

    public static function createJob(array $opts): int
    {
        QubitPdo::modify(
            'INSERT INTO subtree_export
             (start_slug, start_id, start_lft, bound_slug, bound_lft, bound_rgt,
              max_items, batch_size,
              include_masters, include_references, include_thumbnails, include_metadata,
              output_path, status, bytes_estimated, items_total, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())',
            [
                $opts['slug'], $opts['start']['id'], $opts['start']['lft'],
                $opts['start']['bound_slug'], $opts['start']['bound_lft'], $opts['start']['bound_rgt'],
                $opts['max_items'], $opts['batch_size'],
                (int) $opts['masters'], (int) $opts['references'], (int) $opts['thumbnails'],
                (int) $opts['metadata'],
                $opts['output'], 'pending', $opts['bytes'], $opts['items'],
            ]
        );

        return (int) QubitPdo::lastInsertId();
    }

    /**
     * Refuse to start unless the destination has room, with headroom.
     *
     * This exists because the obvious destination is the wrong one: on RARI the AtoM
     * root sits on a 62G filesystem with 32G free, and a 20.67 GB export would leave
     * a production root filesystem with 11G. The export goes to external storage,
     * and this stops a mistyped --output quietly filling the wrong disk.
     *
     * @param float $headroom multiple of the estimate that must be free
     */
    public static function destinationHasRoom(string $path, int $bytesNeeded, float $headroom = 1.5): array
    {
        $probe = $path;
        while (!is_dir($probe) && '/' !== $probe && '' !== $probe) {
            $probe = dirname($probe);
        }

        $free = @disk_free_space($probe);

        if (false === $free) {
            // Unknown is not the same as insufficient. Report it and let the caller
            // decide rather than blocking an export on a filesystem we cannot stat.
            return ['ok' => true, 'free' => null, 'probe' => $probe, 'unknown' => true];
        }

        $required = (int) ceil($bytesNeeded * $headroom);

        return [
            'ok' => $free >= $required,
            'free' => (int) $free,
            'required' => $required,
            'probe' => $probe,
            'unknown' => false,
        ];
    }

    public static function job(int $id)
    {
        return QubitPdo::fetchOne('SELECT * FROM subtree_export WHERE id = ?', [$id]);
    }

    /**
     * Drain one batch, or everything when batch_size is 0.
     *
     * Returns a summary array. The caller decides whether to loop; keeping the loop
     * outside means a batched run can stop cleanly between batches rather than
     * being interrupted mid-copy.
     */
    public static function drain(object $job, string $atomRoot, ?callable $log = null): array
    {
        $start = [
            'id' => (int) $job->start_id,
            'lft' => (int) $job->start_lft,
            'bound_lft' => (int) $job->bound_lft,
            'bound_rgt' => (int) $job->bound_rgt,
        ];
        $usages = SubtreeExportService::usagesFor([
            'masters' => $job->include_masters,
            'references' => $job->include_references,
            'thumbnails' => $job->include_thumbnails,
        ]);

        $remaining = max(0, (int) $job->max_items - (int) $job->items_done);
        $take = (int) $job->batch_size > 0 ? min((int) $job->batch_size, $remaining) : $remaining;

        if ($take < 1) {
            self::finish($job->id, 'completed');

            return ['items' => 0, 'files' => 0, 'bytes' => 0, 'missing' => 0, 'done' => true];
        }

        QubitPdo::modify(
            "UPDATE subtree_export SET status='running', started_at=COALESCE(started_at, NOW()) WHERE id = ?",
            [$job->id]
        );

        $cursor = null === $job->resume_after_lft ? null : (int) $job->resume_after_lft;
        $items = SubtreeExportService::selectItems($start, $take, $cursor);

        if (empty($items)) {
            self::finish($job->id, 'completed');

            return ['items' => 0, 'files' => 0, 'bytes' => 0, 'missing' => 0, 'done' => true];
        }

        $ids = array_map(static function ($r) { return (int) $r->id; }, $items);
        $files = SubtreeExportService::filesFor($ids, $usages);

        $bytes = $copied = $missing = $mismatched = $failed = 0;

        foreach ($files as $f) {
            $src = SubtreeExportService::absolutePath($atomRoot, $f);

            // Package layout mirrors the source tree under objects/, so a file's
            // provenance stays readable without consulting the manifest.
            $rel = 'objects/'.trim((string) $f->path, '/').'/'.ltrim((string) $f->name, '/');
            $dst = rtrim($job->output_path, '/').'/'.$rel;

            if (!is_readable($src)) {
                ++$missing;
                self::recordFile($job->id, $f, $src, $rel, 'missing');
                if ($log) {
                    $log('missing on disk: '.$src);
                }

                continue;
            }

            if (!is_dir(dirname($dst)) && !@mkdir(dirname($dst), 0775, true) && !is_dir(dirname($dst))) {
                ++$failed;
                self::recordFile($job->id, $f, $src, $rel, 'failed');
                if ($log) {
                    $log('cannot create '.dirname($dst).' - check the destination is writable');
                }

                continue;
            }

            // copy() rather than a stream loop: PHP streams it internally and this
            // runs on the AtoM host, where source and destination are both local.
            if (@copy($src, $dst)) {
                ++$copied;

                // What actually landed, NOT digital_object.byte_size. The catalogue
                // figure is what the file was when it was catalogued; using it here
                // would report a full 20 GB export as complete even if every file
                // arrived truncated. filesize() is the only number that is evidence.
                $written = @filesize($dst);
                $actual = false === $written ? (int) $f->byte_size : (int) $written;
                $bytes += $actual;

                // A size the catalogue disagrees with means the source changed, the
                // copy was short, or the record is stale. Worth saying out loud
                // rather than silently accepting either number.
                if (false !== $written && (int) $f->byte_size > 0 && $actual !== (int) $f->byte_size) {
                    ++$mismatched;
                    if ($log) {
                        $log(sprintf(
                            'size differs from the catalogue: %s (on disk %d, recorded %d)',
                            $rel, $actual, (int) $f->byte_size
                        ));
                    }
                }

                self::recordFile($job->id, $f, $src, $rel, 'copied', $actual);
            } else {
                ++$failed;
                self::recordFile($job->id, $f, $src, $rel, 'failed');
                if ($log) {
                    $log('copy failed: '.$rel);
                }
            }
        }

        if ($job->include_metadata) {
            self::writeMetadata($job, $items, $files);
        }

        $lastLft = (int) end($items)->lft;

        QubitPdo::modify(
            'UPDATE subtree_export
                SET items_done = items_done + ?, files_done = files_done + ?,
                    bytes_written = bytes_written + ?, files_missing = files_missing + ?,
                    resume_after_lft = ?, batch_index = batch_index + 1
              WHERE id = ?',
            [count($items), $copied, $bytes, $missing, $lastLft, $job->id]
        );

        $fresh = self::job((int) $job->id);
        $done = (int) $fresh->items_done >= (int) $fresh->max_items;

        if ($done) {
            // Written before finish() so the index reflects the final counters.
            self::writeIndex(self::job((int) $job->id));

            // Files were expected and none arrived: that is a failed run, whatever
            // the counters say. Reporting "completed" for a job that wrote nothing
            // is how an operator walks away believing 20 GB moved.
            self::finish($job->id, (count($files) > 0 && 0 === $copied) ? 'failed' : 'completed');
        } elseif ((int) $job->batch_size > 0) {
            QubitPdo::modify("UPDATE subtree_export SET status='paused' WHERE id = ?", [$job->id]);
        }

        return [
            'items' => count($items), 'files' => $copied, 'bytes' => $bytes,
            'missing' => $missing, 'mismatched' => $mismatched, 'failed' => $failed,
            'done' => $done,
        ];
    }


    /**
     * One lookup file for the whole export, written when the job finishes.
     *
     * The per-batch files are a record of each pass; this is the thing you actually
     * search. Built from subtree_export_file rather than from those batch files, so
     * it describes what HAPPENED - including anything missing or failed - rather
     * than what was selected.
     *
     * Deliberately one flat file with no index structure: an archivist with a drive
     * and no tooling can open it, grep it, or load it into anything. A format that
     * needs software to read defeats the point of an offline package.
     */
    private static function writeIndex(object $job): void
    {
        $dir = rtrim($job->output_path, '/').'/metadata';

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }

        try {
            $rows = QubitPdo::fetchAll(
                'SELECT f.information_object_id, f.usage_id, f.relative_path, f.byte_size,
                        f.checksum, f.status, io.identifier, io.lft, s.slug
                   FROM subtree_export_file f
              LEFT JOIN information_object io ON io.id = f.information_object_id
              LEFT JOIN slug s ON s.object_id = f.information_object_id
                  WHERE f.export_id = ?
               ORDER BY io.lft, f.usage_id',
                [(int) $job->id]
            );
        } catch (\Throwable $e) {
            return;
        }

        $records = [];

        foreach ($rows as $r) {
            $id = (int) $r->information_object_id;

            if (!isset($records[$id])) {
                $records[$id] = [
                    'id' => $id,
                    'slug' => $r->slug,
                    'identifier' => $r->identifier,
                    'lft' => null === $r->lft ? null : (int) $r->lft,
                    'files' => [],
                ];
            }

            $records[$id]['files'][] = [
                'usage_id' => (int) $r->usage_id,
                'usage' => self::usageName((int) $r->usage_id),
                'path' => $r->relative_path,
                'byte_size' => (int) $r->byte_size,
                'checksum' => $r->checksum,
                'status' => $r->status,
            ];
        }

        self::enrich($records);

        // Same envelope as ahgPortableExportPlugin's manifest.json on PSIS, so it
        // reads familiarly to anyone who has handled one of those packages. The
        // format string differs on purpose: this is a subtree export, not a portable
        // catalogue, and claiming the same format identifier while carrying a
        // different structure would mislead anything that reads it.
        $manifest = [
            'version' => '1.0.0',
            'format' => 'atom-heratio-subtree-export',
            'created_at' => date('c'),
            'source' => [
                'url' => sfConfig::get('app_siteBaseUrl', ''),
                'site_title' => sfConfig::get('app_siteTitle', 'AtoM'),
                'plugin_version' => '1.0.0',
            ],
            'scope' => [
                'type' => 'subtree',
                'start_slug' => $job->start_slug,
                'bound_slug' => $job->bound_slug,
                'max_items' => (int) $job->max_items,
            ],
            'counts' => [
                'records' => count($records),
                'files' => count($rows),
                'bytes_written' => (int) $job->bytes_written,
                'files_missing' => (int) $job->files_missing,
            ],
            'records' => array_values($records),
        ];

        $root = rtrim($job->output_path, '/');
        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // At the package root, not under metadata/, so it is the first thing
        // anyone opening the drive sees.
        file_put_contents($root.'/manifest.json', $json);

        // The same data as a script, because browsers refuse fetch() on file://
        // for local files. Without this, lookup.html opened from a drive - which is
        // the entire point of an offline package - would load nothing at all.
        // ahgPortableExportPlugin's ViewerPackager does the same for its config.
        file_put_contents($root.'/manifest.js', 'window.SUBTREE_MANIFEST = '.$json.';');

        // The browsable lookup, copied from the plugin rather than generated, so it
        // can be edited as a normal HTML file.
        $viewer = __DIR__.'/../../web/lookup.html';

        if (is_readable($viewer)) {
            copy($viewer, $root.'/lookup.html');
        }
    }

    /**
     * Add the descriptive metadata worth searching on.
     *
     * Two queries for the whole export rather than one per record: a thousand
     * records would otherwise be two thousand round trips.
     *
     * i18n rows are joined on the record's OWN source_culture rather than a fixed
     * 'en'. A record catalogued in Afrikaans has no English row, and joining on
     * 'en' would silently give it a blank title - present in the manifest, missing
     * from every search.
     *
     * @param array $records keyed by information object id, modified in place
     */
    private static function enrich(array &$records): void
    {
        if (empty($records)) {
            return;
        }

        $ids = implode(',', array_map('intval', array_keys($records)));

        try {
            $rows = QubitPdo::fetchAll(
                "SELECT io.id, io.source_standard, io.shelf,
                        i18n.title, i18n.alternate_title, i18n.scope_and_content,
                        i18n.extent_and_medium, i18n.physical_characteristics,
                        i18n.archival_history, i18n.access_conditions,
                        lod.name AS level_of_description,
                        repo.authorized_form_of_name AS repository
                   FROM information_object io
              LEFT JOIN information_object_i18n i18n
                     ON i18n.id = io.id AND i18n.culture = io.source_culture
              LEFT JOIN term_i18n lod
                     ON lod.id = io.level_of_description_id AND lod.culture = 'en'
              LEFT JOIN actor_i18n repo
                     ON repo.id = io.repository_id AND repo.culture = 'en'
                  WHERE io.id IN ({$ids})"
            );
        } catch (\Throwable $e) {
            return;
        }

        foreach ($rows as $r) {
            $id = (int) $r->id;

            if (!isset($records[$id])) {
                continue;
            }

            $records[$id] += [
                'title' => $r->title,
                'alternate_title' => $r->alternate_title,
                'level_of_description' => $r->level_of_description,
                'repository' => $r->repository,
                'scope_and_content' => $r->scope_and_content,
                'extent_and_medium' => $r->extent_and_medium,
                'physical_characteristics' => $r->physical_characteristics,
                'archival_history' => $r->archival_history,
                'access_conditions' => $r->access_conditions,
                'source_standard' => $r->source_standard,
                'shelf' => $r->shelf,
                'dates' => [],
                'creators' => [],
                'access_points' => [],
            ];
        }

        // Dates and creators come from events, which are rows not columns: one
        // record can carry a creation date, a custodial history date and several
        // actors, so they cannot be folded into the query above.
        try {
            $events = QubitPdo::fetchAll(
                "SELECT e.object_id, e.start_date, e.end_date,
                        ei.date AS display_date,
                        a.authorized_form_of_name AS actor,
                        et.name AS event_type
                   FROM event e
              LEFT JOIN event_i18n ei ON ei.id = e.id AND ei.culture = 'en'
              LEFT JOIN actor_i18n a ON a.id = e.actor_id AND a.culture = 'en'
              LEFT JOIN term_i18n et ON et.id = e.type_id AND et.culture = 'en'
                  WHERE e.object_id IN ({$ids})"
            );
        } catch (\Throwable $e) {
            return;
        }

        foreach ($events as $e) {
            $id = (int) $e->object_id;

            if (!isset($records[$id])) {
                continue;
            }

            $shown = $e->display_date ?: trim((string) $e->start_date.(
                $e->end_date && $e->end_date !== $e->start_date ? ' - '.$e->end_date : ''
            ));

            if ('' !== (string) $shown) {
                $records[$id]['dates'][] = ['type' => $e->event_type, 'date' => $shown];
            }

            if (!empty($e->actor) && !in_array($e->actor, $records[$id]['creators'], true)) {
                $records[$id]['creators'][] = $e->actor;
            }
        }

        // Access points: places, subjects and genres. On this collection they are
        // the richest thing in the catalogue - scope and content is filled on 9
        // records in a thousand, while every record carries a place. A search that
        // skipped these would miss the field people actually look things up by.
        try {
            $terms = QubitPdo::fetchAll(
                "SELECT otr.object_id, ti.name AS term, tx.name AS taxonomy
                   FROM object_term_relation otr
                   JOIN term t ON t.id = otr.term_id
              LEFT JOIN term_i18n ti ON ti.id = t.id AND ti.culture = 'en'
              LEFT JOIN taxonomy_i18n tx ON tx.id = t.taxonomy_id AND tx.culture = 'en'
                  WHERE otr.object_id IN ({$ids})"
            );
        } catch (\Throwable $e) {
            return;
        }

        foreach ($terms as $t) {
            $id = (int) $t->object_id;

            if (!isset($records[$id]) || empty($t->term)) {
                continue;
            }

            // Grouped by taxonomy so a reader can tell a place from a genre.
            $group = strtolower((string) ($t->taxonomy ?: 'other'));

            if (!isset($records[$id]['access_points'][$group])) {
                $records[$id]['access_points'][$group] = [];
            }

            if (!in_array($t->term, $records[$id]['access_points'][$group], true)) {
                $records[$id]['access_points'][$group][] = $t->term;
            }
        }
    }

    /** Usage ids mean nothing to a reader; names do. */
    private static function usageName(int $usageId): string
    {
        switch ($usageId) {
            case SubtreeExportService::USAGE_MASTER: return 'master';
            case SubtreeExportService::USAGE_REFERENCE: return 'reference';
            case SubtreeExportService::USAGE_THUMBNAIL: return 'thumbnail';
            default: return 'usage-'.$usageId;
        }
    }

    /** One JSON file per batch, so a resumed run never rewrites an earlier one. */
    private static function writeMetadata(object $job, array $items, array $files): void
    {
        $dir = rtrim($job->output_path, '/').'/metadata';

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }

        $byIo = [];
        foreach ($files as $f) {
            $byIo[(int) $f->information_object_id][] = [
                'usage_id' => (int) $f->usage_id,
                'name' => $f->name,
                'byte_size' => (int) $f->byte_size,
                'checksum' => $f->checksum,
                'relative_path' => 'objects/'.trim((string) $f->path, '/').'/'.ltrim((string) $f->name, '/'),
            ];
        }

        $out = [];
        foreach ($items as $i) {
            $out[] = [
                'id' => (int) $i->id,
                'slug' => $i->slug,
                'identifier' => $i->identifier,
                'lft' => (int) $i->lft,
                'files' => $byIo[(int) $i->id] ?? [],
            ];
        }

        file_put_contents(
            sprintf('%s/batch-%04d.json', $dir, (int) $job->batch_index),
            json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * INSERT IGNORE on (export_id, digital_object_id): a resumed batch that overlaps
     * an interrupted one records the file once and does not double-count bytes.
     */
    private static function recordFile(int $exportId, object $f, string $src, string $rel, string $status, ?int $actualBytes = null): void
    {
        QubitPdo::modify(
            'INSERT IGNORE INTO subtree_export_file
             (export_id, digital_object_id, information_object_id, usage_id,
              source_path, relative_path, byte_size, checksum, status, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,NOW())',
            [
                $exportId, (int) $f->id, (int) $f->information_object_id, (int) $f->usage_id,
                $src, $rel, $actualBytes ?? (int) $f->byte_size, $f->checksum, $status,
            ]
        );
    }

    private static function finish(int $id, string $status): void
    {
        QubitPdo::modify(
            'UPDATE subtree_export SET status = ?, completed_at = NOW() WHERE id = ?',
            [$status, $id]
        );
    }
}
