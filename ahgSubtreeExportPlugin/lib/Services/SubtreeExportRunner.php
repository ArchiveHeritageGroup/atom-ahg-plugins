<?php

/**
 * Job creation and draining for ahgSubtreeExportPlugin. AtoM 2.8.1, QubitPdo only.
 *
 * A job is created once with its subtree bounds fixed, then drained: in a single
 * pass (batch_size 0) or in sequential batches that resume from resume_after_lft.
 */
class SubtreeExportRunner
{
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

        $bytes = $copied = $missing = $mismatched = 0;

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
                self::recordFile($job->id, $f, $src, $rel, 'failed');

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
                self::recordFile($job->id, $f, $src, $rel, 'failed');
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
            self::finish($job->id, 'completed');
        } elseif ((int) $job->batch_size > 0) {
            QubitPdo::modify("UPDATE subtree_export SET status='paused' WHERE id = ?", [$job->id]);
        }

        return [
            'items' => count($items), 'files' => $copied, 'bytes' => $bytes,
            'missing' => $missing, 'mismatched' => $mismatched, 'done' => $done,
        ];
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
