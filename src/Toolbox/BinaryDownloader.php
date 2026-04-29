<?php

namespace Genericmilk\Cooker\Toolbox;

use GuzzleHttp\Client;
use PharData;
use RuntimeException;

class BinaryDownloader
{
    public function __construct(
        protected ?Client $http = null,
    ) {
        $this->http ??= new Client(['timeout' => 120]);
    }

    public function download(string $url, string $destination): string
    {
        $this->ensureDirectory(dirname($destination));

        $tmp = $destination.'.part';
        $resource = fopen($tmp, 'w');
        if ($resource === false) {
            throw new RuntimeException("Could not open temp file for writing: $tmp");
        }

        $this->http->get($url, ['sink' => $resource]);

        rename($tmp, $destination);
        return $destination;
    }

    public function extractTgz(string $archive, string $destination, string $innerPath): string
    {
        $this->ensureDirectory($destination);

        $workDir = $destination.'/.extract-'.bin2hex(random_bytes(4));
        mkdir($workDir, 0777, true);

        $tarPath = $workDir.'/archive.tar';
        $this->gunzip($archive, $tarPath);

        $phar = new PharData($tarPath);
        $phar->extractTo($workDir, null, true);

        $extractedBinary = $workDir.'/'.$innerPath;
        if (!is_file($extractedBinary)) {
            $this->rrmdir($workDir);
            throw new RuntimeException("Expected binary not found in archive at: $innerPath");
        }

        $finalPath = $destination.'/'.basename($innerPath);
        copy($extractedBinary, $finalPath);
        chmod($finalPath, 0755);

        $this->rrmdir($workDir);
        @unlink($archive);

        return $finalPath;
    }

    protected function gunzip(string $gz, string $out): void
    {
        $in  = gzopen($gz, 'rb');
        $dst = fopen($out, 'wb');

        if ($in === false || $dst === false) {
            throw new RuntimeException('Could not open gzip stream.');
        }

        while (!gzeof($in)) {
            fwrite($dst, gzread($in, 65536));
        }

        gzclose($in);
        fclose($dst);
    }

    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    protected function rrmdir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $path.'/'.$entry;
            is_dir($full) ? $this->rrmdir($full) : @unlink($full);
        }
        @rmdir($path);
    }
}
