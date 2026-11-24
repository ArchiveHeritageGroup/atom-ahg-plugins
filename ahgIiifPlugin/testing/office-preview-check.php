<?php

/**
 * Offline check for the Office display copy converter (#231).
 *
 *   php ahgIiifPlugin/testing/office-preview-check.php [--require-sandbox]
 *
 * Generates its own samples in a temporary folder. No Symfony, no database and no
 * site files. Where the real sandbox cannot start, the conversion still runs on the
 * generated samples without it and the output says so; --require-sandbox makes that
 * a failure, which is how to run this on a host that will do real conversions.
 */

// The plugin folder sits under the web root: never run from a web request.
if ('cli' !== PHP_SAPI) {
    http_response_code(404);

    exit;
}

require_once __DIR__.'/../lib/Services/OfficePreviewConverter.php';

use AhgIiif\Services\OfficePreviewConverter;

/** Test double for trusted generated samples only. Never used by the task. */
class UnsandboxedSampleConverter extends OfficePreviewConverter
{
    protected function sandboxReady(): bool
    {
        return true;
    }

    protected function guestPath(string $job): string
    {
        return $job;
    }

    protected function wrap(array $command, string $job): array
    {
        return $command;
    }
}

function check(string $label, bool $passed): void
{
    global $failures;
    echo ($passed ? 'PASS ' : 'FAIL ').$label."\n";
    $failures += $passed ? 0 : 1;
}

function zipSample(string $path, array $entries): void
{
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE);
    foreach ($entries as $name => $content) {
        $zip->addFromString($name, $content);
        if ('mimetype' === $name) {
            $zip->setCompressionName($name, ZipArchive::CM_STORE);
        }
    }
    $zip->close();
}

$failures = 0;
$marker = 'AHGSAMPLE231';
$root = sys_get_temp_dir().'/ahg-office-check-'.bin2hex(random_bytes(6));
mkdir($root, 0700);

file_put_contents($root.'/sample.rtf', '{\rtf1\ansi '.$marker.' rich text\par}');
zipSample($root.'/sample.docx', [
    '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>',
    '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
    'word/document.xml' => '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>'.$marker.' word</w:t></w:r></w:p></w:body></w:document>',
]);
zipSample($root.'/sample.ods', [
    'mimetype' => 'application/vnd.oasis.opendocument.spreadsheet',
    'META-INF/manifest.xml' => '<?xml version="1.0" encoding="UTF-8"?><manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0"><manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.spreadsheet"/><manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/></manifest:manifest>',
    'content.xml' => '<?xml version="1.0" encoding="UTF-8"?><office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" office:version="1.2"><office:body><office:spreadsheet><table:table table:name="S"><table:table-row><table:table-cell office:value-type="string"><text:p>'.$marker.' sheet</text:p></table:table-cell></table:table-row></table:table></office:spreadsheet></office:body></office:document-content>',
]);

$real = new OfficePreviewConverter(['work_root' => $root]);
$sandboxed = $real->sandboxAvailable();
echo 'Sandbox: '.($sandboxed ? 'available' : 'NOT available on this host/user')."\n";

if (!$sandboxed) {
    $refused = $real->convert($root.'/sample.rtf', 'rtf', $root.'/refused.pdf');
    check('refuses to convert without a sandbox', !$refused['ok'] && !file_exists($root.'/refused.pdf'));
}
check('rejects an unsupported type', !$real->convert($root.'/sample.rtf', 'exe', $root.'/x.pdf')['ok']);
symlink($root.'/sample.rtf', $root.'/link.rtf');
check('rejects a symlinked source', !$real->convert($root.'/link.rtf', 'rtf', $root.'/x.pdf')['ok']);
$xml = OfficePreviewConverter::profileXml();
check('profile disables macros', str_contains($xml, 'DisableMacrosExecution') && str_contains($xml, '<value>3</value>'));
check('profile is well-formed', false !== @simplexml_load_string($xml));

$converter = $sandboxed ? $real : new UnsandboxedSampleConverter(['work_root' => $root, 'timeout' => 120]);
foreach (['rtf', 'docx', 'ods'] as $extension) {
    $target = $root.'/out/'.$extension.'.pdf';
    $result = $converter->convert($root.'/sample.'.$extension, $extension, $target);
    check($extension.' converts', $result['ok']);
    check($extension.' output is a PDF', is_file($target) && '%PDF-' === file_get_contents($target, false, null, 0, 5));
    check($extension.' text extracted', str_contains((string) $result['text'], $marker));
}
check('job directories removed', [] === glob($root.'/ahg-office-*'));

if (!$sandboxed && in_array('--require-sandbox', $argv, true)) {
    check('sandbox required', false);
}

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $entry) {
    $entry->isDir() && !$entry->isLink() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
}
rmdir($root);

echo $failures ? "{$failures} check(s) failed\n" : "All checks passed\n";
exit($failures ? 1 : 0);
