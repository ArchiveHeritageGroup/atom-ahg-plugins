<?php

/**
 * OcflStorageAdapter - local-filesystem backing store for an OCFL storage root.
 *
 * The Heratio package wraps Laravel's Filesystem contract (so it can target
 * local disk, S3, Wasabi, etc.). AtoM/Symfony 1.x has no Flysystem/Storage
 * facade, so this adapter implements the same minimal contract
 * (exists/get/put/putFromFile/files/delete) directly against the local
 * filesystem, rooted at an absolute directory. All paths are relative to
 * that root; traversal outside the root is rejected.
 *
 * Streaming putFromFile() copies preservation masters without buffering the
 * whole file into PHP memory.
 *
 * @copyright  The Archive and Heritage Group (Pty) Ltd
 * @license    AGPL-3.0-or-later
 */

namespace AtomExtensions\Ocfl\Storage;

use RuntimeException;

class OcflStorageAdapter
{
    private string $root;

    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/');
    }

    public function root(): string
    {
        return $this->root;
    }

    public function exists(string $path): bool
    {
        return file_exists($this->resolve($path));
    }

    public function get(string $path): string
    {
        $full = $this->resolve($path);
        $contents = @file_get_contents($full);

        return false === $contents ? '' : (string) $contents;
    }

    public function put(string $path, string $contents): void
    {
        $full = $this->resolve($path);
        $this->ensureDir(dirname($full));
        if (false === @file_put_contents($full, $contents)) {
            throw new RuntimeException("OcflStorageAdapter: cannot write {$full}");
        }
    }

    /**
     * Write a file from a local path. Streams the copy so multi-GB
     * preservation masters do not balloon PHP memory.
     */
    public function putFromFile(string $path, string $localFile): void
    {
        $full = $this->resolve($path);
        $this->ensureDir(dirname($full));

        $in = @fopen($localFile, 'rb');
        if (false === $in) {
            throw new RuntimeException("OcflStorageAdapter: cannot open {$localFile}");
        }
        $out = @fopen($full, 'wb');
        if (false === $out) {
            fclose($in);
            throw new RuntimeException("OcflStorageAdapter: cannot open {$full} for writing");
        }
        try {
            while (!feof($in)) {
                $chunk = fread($in, 1048576);
                if (false === $chunk) {
                    break;
                }
                fwrite($out, $chunk);
            }
        } finally {
            fclose($in);
            fclose($out);
        }
    }

    /** Recursively list all files under the given prefix (relative paths). */
    public function files(string $prefix = ''): array
    {
        $base = $this->resolve($prefix);
        if (!is_dir($base)) {
            return [];
        }
        $result = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $rootLen = strlen($this->root) + 1;
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $result[] = substr($file->getPathname(), $rootLen);
            }
        }
        sort($result, SORT_STRING);

        return $result;
    }

    public function delete(string $path): void
    {
        $full = $this->resolve($path);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("OcflStorageAdapter: cannot create directory {$dir}");
        }
    }

    private function resolve(string $path): string
    {
        $clean = ltrim(str_replace(['../', '..\\'], '', $path), '/');

        return '' === $clean ? $this->root : $this->root . '/' . $clean;
    }
}
