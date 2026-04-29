<?php

namespace Genericmilk\Cooker\Toolbox;

use RuntimeException;

class Esbuild
{
    public function __construct(
        protected string $binDir,
        protected string $version,
        protected ?BinaryDownloader $downloader = null,
    ) {
        $this->downloader ??= new BinaryDownloader();
    }

    public function path(): string
    {
        return $this->binDir.'/esbuild'.Platform::exeSuffix();
    }

    public function isInstalled(): bool
    {
        return is_file($this->path()) && is_executable($this->path());
    }

    public function ensureInstalled(): string
    {
        if ($this->isInstalled()) {
            return $this->path();
        }
        return $this->install();
    }

    public function install(): string
    {
        $os   = Platform::os();
        $arch = Platform::arch();

        $pkg = "@esbuild/{$os}-{$arch}";
        $url = "https://registry.npmjs.org/{$pkg}/-/{$os}-{$arch}-{$this->version}.tgz";

        $archivePath = $this->binDir.'/esbuild.tgz';
        $this->downloader->download($url, $archivePath);

        $innerPath = Platform::isWindows()
            ? 'package/esbuild.exe'
            : 'package/bin/esbuild';

        return $this->downloader->extractTgz($archivePath, $this->binDir, $innerPath);
    }

    /**
     * Run esbuild with the given args. Returns [exitCode, stdout, stderr].
     *
     * @param array<int, string>      $args
     * @param array<string, string>|null $env  Extra env vars merged with the inherited environment.
     */
    public function run(array $args, ?string $cwd = null, ?array $env = null, bool $inherit = false): array
    {
        $bin = $this->ensureInstalled();
        $cmd = array_merge([$bin], $args);

        $mergedEnv = $env === null ? null : array_merge(getenv(), $env);

        if ($inherit) {
            $process = proc_open($cmd, [STDIN, STDOUT, STDERR], $pipes, $cwd, $mergedEnv);
            if (!is_resource($process)) {
                throw new RuntimeException('Failed to start esbuild.');
            }
            return [proc_close($process), '', ''];
        }

        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $cwd, $mergedEnv);
        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start esbuild.');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exit = proc_close($process);
        return [$exit, $stdout, $stderr];
    }
}
