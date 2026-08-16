<?php

/**
 * Standalone map sheet importer.
 *
 * Does what `php symfony siterecord:import-mapsheets` does, for instances whose
 * CLI does not load AHG plugins and therefore cannot see the task at all. The
 * RARI development instance is one: it enables plugins through the serialised
 * `plugins` setting at runtime rather than the hardcoded CLI list, so no AHG
 * task is discoverable there. PSIS loads plugins from atom_plugin and sees the
 * task normally - prefer the task where it works.
 *
 * Both paths call LocalityTextParser, so there is one implementation of the
 * parsing, not two that drift.
 *
 * Usage:
 *   php import-mapsheets.php --db=atom --user=atom --password-file=/path [--apply] [--limit=N] [--update]
 *
 * Reports without writing unless --apply is given.
 */

require_once dirname(__DIR__).'/lib/Services/LocalityTextParser.php';

use AhgSiteRecordPlugin\Services\LocalityTextParser as Parser;

$opt = getopt('', ['db:', 'user:', 'password-file:', 'password:', 'host::', 'apply', 'limit::', 'update', 'show-unparsed', 'move']);

foreach (['db', 'user'] as $required) {
    if (empty($opt[$required])) {
        fwrite(STDERR, "missing --{$required}\n");
        exit(1);
    }
}

$password = isset($opt['password-file'])
    ? trim((string) file_get_contents($opt['password-file']))
    : (string) ($opt['password'] ?? '');

$host = $opt['host'] ?? 'localhost';
$apply = isset($opt['apply']);
$update = isset($opt['update']);

$pdo = new PDO("mysql:host={$host};dbname={$opt['db']};charset=utf8mb4", $opt['user'], $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --move: clear the ISAAR field, leaving the site record as the only copy.
//
// Copying the locality into ahg_site_record gates the structured copy, but the
// original field keeps showing the same information to anyone who can read the
// authority record. On RARI that includes 842 records whose field holds
// "How to find the site:" followed by turn-by-turn driving directions - far more
// revealing than the map sheet, which only resolves to a few kilometres.
//
// Only clears a field whose text is ALREADY held verbatim in
// locality_original. A record that does not match is reported and left alone, so
// the move cannot destroy anything the seed did not capture.
if (isset($opt['move'])) {
    moveOutOfIsaar($pdo, isset($opt['apply']));

    exit(0);
}

function moveOutOfIsaar(PDO $pdo, bool $apply): void
{
    $rows = $pdo->query(
        'SELECT ai.id, ai.internal_structures AS raw, r.locality_original AS kept
         FROM actor_i18n ai
         JOIN ahg_site_record r ON r.actor_id = ai.id
         WHERE ai.internal_structures IS NOT NULL AND ai.internal_structures <> ""'
    )->fetchAll(PDO::FETCH_ASSOC);

    $clear = $pdo->prepare('UPDATE actor_i18n SET internal_structures = NULL WHERE id = ?');

    $moved = $mismatch = 0;
    $samples = [];

    foreach ($rows as $row) {
        // Compare normalised forms: the stored copy has had its markup flattened,
        // so a raw string comparison would report every record as a mismatch.
        if (Parser::normalise($row['raw']) !== (string) $row['kept']) {
            ++$mismatch;
            if (count($samples) < 8) {
                $samples[] = $row['id'];
            }
            continue;
        }

        if ($apply) {
            $clear->execute([(int) $row['id']]);
        }
        ++$moved;
    }

    printf("    %s %d authority records\n", $apply ? 'Examined' : 'Would examine', count($rows));
    printf("      %-30s : %d\n", $apply ? 'field cleared' : 'would clear the field', $moved);
    printf("      %-30s : %d  (left untouched)\n", 'text not held in the plugin', $mismatch);

    if ($samples) {
        printf("      mismatched ids: %s\n", implode(', ', $samples));
    }

    if (!$apply) {
        echo "    Dry run. Re-run with --apply to write.\n";
    }
}

$sql = 'SELECT ai.id, ai.internal_structures, ai.authorized_form_of_name AS name
        FROM actor_i18n ai
        JOIN actor a ON a.id = ai.id
        WHERE ai.internal_structures IS NOT NULL AND ai.internal_structures <> "" AND a.id > 6
        ORDER BY ai.id';
if (!empty($opt['limit'])) {
    $sql .= ' LIMIT '.(int) $opt['limit'];
}

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$existing = array_flip(array_map('intval',
    $pdo->query('SELECT actor_id FROM ahg_site_record')->fetchAll(PDO::FETCH_COLUMN)));

$insert = $pdo->prepare(
    'INSERT INTO ahg_site_record (actor_id, map_sheet, locality_original, locality_sensitive, created_at, updated_at)
     VALUES (?, ?, ?, 1, NOW(), NOW())');
$updateStmt = $pdo->prepare(
    'UPDATE ahg_site_record SET map_sheet = ?, locality_original = ?, updated_at = NOW() WHERE actor_id = ?');

$created = $updated = $skipped = $noSheet = $unparsed = 0;
$sheets = [];
$unparsedSamples = [];

foreach ($rows as $row) {
    $actorId = (int) $row['id'];
    $text = Parser::normalise($row['internal_structures']);

    if ('' === $text) {
        ++$unparsed;
        if (count($unparsedSamples) < 10) {
            $unparsedSamples[] = $actorId.'  '.($row['name'] ?? '');
        }
        continue;
    }

    $sheet = Parser::mapSheet($text);
    null === $sheet ? ++$noSheet : $sheets[$sheet] = true;

    $has = isset($existing[$actorId]);

    // Never touch a record someone has already filled in by hand, unless asked.
    if ($has && !$update) {
        ++$skipped;
        continue;
    }

    if ($apply) {
        $has
            ? $updateStmt->execute([$sheet, $text, $actorId])
            : $insert->execute([$actorId, $sheet, $text]);
    }

    $has ? ++$updated : ++$created;
}

printf("    %s %d authority records carrying locality text\n", $apply ? 'Processed' : 'Would process', count($rows));
printf("      %-26s : %d\n", $apply ? 'created' : 'would create', $created);
printf("      %-26s : %d\n", $apply ? 'updated' : 'would update', $updated);
printf("      %-26s : %d\n", 'skipped, already present', $skipped);
printf("      %-26s : %d  (locality text still kept)\n", 'no map sheet found', $noSheet);
printf("      %-26s : %d\n", 'nothing readable', $unparsed);
printf("      %-26s : %d\n", 'distinct map sheets', count($sheets));

if ($unparsedSamples && isset($opt['show-unparsed'])) {
    echo "      unreadable:\n";
    foreach ($unparsedSamples as $s) {
        echo '        '.$s."\n";
    }
}

if (!$apply) {
    echo "    Dry run. Re-run with --apply to write.\n";
}
