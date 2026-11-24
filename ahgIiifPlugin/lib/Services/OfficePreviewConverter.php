<?php

declare(strict_types=1);

namespace AhgIiif\Services;

/**
 * Converts one untrusted Office document to PDF and extracts its text (#231).
 *
 * No Symfony and no database here, so testing/office-preview-check.php can run it
 * offline. Uploaded documents are hostile input to LibreOffice, so each run gets:
 * - a private job directory holding a copy of the file under a fixed name;
 * - a fresh LibreOffice profile with macros off and link updates off;
 * - a bubblewrap sandbox with no network and only an allowlist of system paths,
 *   so a document cannot embed /etc or application config through a linked file;
 * - argv execution (no shell), a hard timeout and resource limits.
 *
 * The sandbox is not optional. Where bubblewrap cannot run, convert() refuses.
 */
class OfficePreviewConverter
{
    public const EXTENSIONS = ['docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'odt', 'ods', 'odp', 'rtf'];

    /** property_i18n.value is MEDIUMTEXT. */
    private const MAX_TEXT_BYTES = 16777215;

    private const GUEST_ROOT = '/job';

    private const SANDBOX_RO_PATHS = [
        '/usr/bin', '/usr/lib', '/usr/lib64', '/usr/share/fonts', '/usr/share/fontconfig',
        '/usr/share/libreoffice', '/usr/share/poppler', '/usr/share/zoneinfo',
        '/etc/fonts', '/etc/libreoffice', '/etc/ld.so.cache',
    ];

    private int $timeout;
    private int $maxInputBytes;
    private int $maxOutputBytes;
    private int $maxMemoryBytes;
    private string $workRoot;

    public function __construct(array $options = [])
    {
        $this->timeout = (int) ($options['timeout'] ?? 180);
        $this->maxInputBytes = (int) ($options['max_input_bytes'] ?? 209715200);
        $this->maxOutputBytes = (int) ($options['max_output_bytes'] ?? 524288000);
        $this->maxMemoryBytes = (int) ($options['max_memory_bytes'] ?? 6442450944);
        $this->workRoot = (string) ($options['work_root'] ?? sys_get_temp_dir());
    }

    /** True when the real sandbox, including its empty network namespace, starts. */
    public function sandboxAvailable(): bool
    {
        if (!is_executable('/usr/bin/bwrap')) {
            return false;
        }
        $dir = $this->makeJobDir();

        try {
            return 0 === $this->run($this->wrap(['/usr/bin/true'], $dir), $dir, 10);
        } finally {
            $this->removeTree($dir);
        }
    }

    /**
     * @return array{ok:bool,error:?string,text:?string}
     */
    public function convert(string $source, string $extension, string $targetPdf): array
    {
        $extension = strtolower($extension);
        if (!in_array($extension, self::EXTENSIONS, true)) {
            return self::failure('Unsupported file type');
        }
        if (is_link($source) || !is_file($source) || !is_readable($source)) {
            return self::failure('Source file not readable');
        }
        $size = (int) filesize($source);
        if (0 === $size || $size > $this->maxInputBytes) {
            return self::failure('Source file is empty or over the size limit');
        }
        if (!$this->sandboxReady()) {
            return self::failure('Sandbox unavailable: bubblewrap cannot create a network-isolated namespace');
        }

        $job = $this->makeJobDir();

        try {
            foreach (['in', 'out', 'home', 'tmp', 'profile/user'] as $sub) {
                mkdir($job.'/'.$sub, 0700, true);
            }
            file_put_contents($job.'/profile/user/registrymodifications.xcu', self::profileXml());
            $input = 'input.'.$extension;
            if (!copy($source, $job.'/in/'.$input)) {
                return self::failure('Could not copy source file');
            }

            $guest = $this->guestPath($job);
            $status = $this->run($this->wrap([
                '/usr/bin/soffice',
                '-env:UserInstallation=file://'.$guest.'/profile',
                '--headless', '--invisible', '--norestore', '--nologo', '--nodefault',
                '--nolockcheck', '--nofirststartwizard',
                '--convert-to', 'pdf',
                '--outdir', $guest.'/out',
                $guest.'/in/'.$input,
            ], $job), $job, $this->timeout);

            $pdf = $job.'/out/input.pdf';
            if (0 !== $status || !self::isRegularFile($pdf)) {
                return self::failure(124 === $status || 137 === $status ? 'Conversion timed out' : 'Conversion failed');
            }
            $pdfSize = (int) filesize($pdf);
            if ($pdfSize > $this->maxOutputBytes || '%PDF-' !== (string) file_get_contents($pdf, false, null, 0, 5)) {
                return self::failure('Conversion produced an invalid PDF');
            }

            $text = $this->extractText($job, $guest);
            if (!$this->publish($pdf, $targetPdf)) {
                return self::failure('Could not store the display copy');
            }

            return ['ok' => true, 'error' => null, 'text' => $text];
        } finally {
            $this->removeTree($job);
        }
    }

    /** Overridden only by the offline check on hosts without a working sandbox. */
    protected function sandboxReady(): bool
    {
        return $this->sandboxAvailable();
    }

    /** Path of the job directory as seen by the sandboxed process. */
    protected function guestPath(string $job): string
    {
        return self::GUEST_ROOT;
    }

    /** @param string[] $command */
    protected function wrap(array $command, string $job): array
    {
        $argv = [
            '/usr/bin/prlimit',
            '--as='.$this->maxMemoryBytes,
            '--fsize='.$this->maxOutputBytes,
            '--core=0',
            '/usr/bin/bwrap',
            '--unshare-all',
            '--die-with-parent',
            '--new-session',
            '--cap-drop', 'ALL',
        ];
        foreach (self::SANDBOX_RO_PATHS as $path) {
            array_push($argv, '--ro-bind-try', $path, $path);
        }
        array_push(
            $argv,
            '--symlink',
            'usr/bin',
            '/bin',
            '--symlink',
            'usr/lib',
            '/lib',
            '--symlink',
            'usr/lib64',
            '/lib64',
            '--proc',
            '/proc',
            '--dev',
            '/dev',
            '--tmpfs',
            '/tmp',
            '--bind',
            $job,
            self::GUEST_ROOT,
            '--chdir',
            self::GUEST_ROOT,
            '--'
        );

        return array_merge($argv, $command);
    }

    private function extractText(string $job, string $guest): ?string
    {
        $status = $this->run($this->wrap([
            '/usr/bin/pdftotext', '-enc', 'UTF-8', '-nopgbrk', $guest.'/out/input.pdf', $guest.'/out/text.txt',
        ], $job), $job, min(60, $this->timeout));

        $file = $job.'/out/text.txt';
        if (0 !== $status || !self::isRegularFile($file)) {
            return null;
        }
        $text = (string) file_get_contents($file, false, null, 0, self::MAX_TEXT_BYTES);
        $text = trim(mb_scrub(mb_strcut($text, 0, self::MAX_TEXT_BYTES, 'UTF-8'), 'UTF-8'));

        return '' === $text ? null : $text;
    }

    /** Atomic, so a reader never sees a half-written PDF. */
    private function publish(string $pdf, string $target): bool
    {
        $dir = dirname($target);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }
        $partial = $target.'.'.bin2hex(random_bytes(6)).'.part';
        if (!copy($pdf, $partial)) {
            @unlink($partial);

            return false;
        }
        chmod($partial, 0640);
        if (!rename($partial, $target)) {
            @unlink($partial);

            return false;
        }

        return true;
    }

    /** @param string[] $argv */
    private function run(array $argv, string $job, int $timeout): int
    {
        $command = array_merge(['/usr/bin/timeout', '--signal=KILL', (string) $timeout], $argv);
        $guest = $this->guestPath($job);
        $process = proc_open($command, [
            0 => ['file', '/dev/null', 'r'],
            1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ], $pipes, $job, [
            'PATH' => '/usr/bin:/bin',
            'HOME' => $guest.'/home',
            'TMPDIR' => $guest.'/tmp',
            'LANG' => 'C.UTF-8',
            'SAL_USE_VCLPLUGIN' => 'svp',
        ]);

        return is_resource($process) ? proc_close($process) : 1;
    }

    private function makeJobDir(): string
    {
        $dir = rtrim($this->workRoot, '/').'/ahg-office-'.bin2hex(random_bytes(12));
        if (!mkdir($dir, 0700, true)) {
            throw new \RuntimeException('Could not create conversion directory');
        }

        return $dir;
    }

    /** Never follows a symlink a converted document may have left behind. */
    private function removeTree(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ('.' !== $entry && '..' !== $entry) {
                $this->removeTree($path.'/'.$entry);
            }
        }
        @rmdir($path);
    }

    private static function isRegularFile(string $path): bool
    {
        return !is_link($path) && is_file($path);
    }

    private static function failure(string $error): array
    {
        return ['ok' => false, 'error' => $error, 'text' => null];
    }

    /** Macro security "very high", macro execution off, linked content never updated. */
    public static function profileXml(): string
    {
        $items = [
            ['/org.openoffice.Office.Common/Security/Scripting', 'MacroSecurityLevel', 'int', '3'],
            ['/org.openoffice.Office.Common/Security/Scripting', 'DisableMacrosExecution', 'boolean', 'true'],
            ['/org.openoffice.Office.Common/Security/Scripting', 'BlockUntrustedRefererLinks', 'boolean', 'true'],
            ['/org.openoffice.Office.Calc/Content/Update', 'Link', 'int', '2'],
            ['/org.openoffice.Office.Writer/Content/Update', 'Link', 'int', '2'],
        ];
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<oor:items xmlns:oor="http://openoffice.org/2001/registry" '
            .'xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'."\n";
        foreach ($items as [$path, $name, $type, $value]) {
            $xml .= sprintf(
                '<item oor:path="%s"><prop oor:name="%s" oor:op="fuse" oor:type="xs:%s"><value>%s</value></prop></item>'."\n",
                $path,
                $name,
                $type,
                $value
            );
        }

        return $xml.'</oor:items>'."\n";
    }
}
