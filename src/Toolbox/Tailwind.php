<?php

namespace Genericmilk\Cooker\Toolbox;

use RuntimeException;

class Tailwind
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
        return $this->binDir.'/tailwindcss'.Platform::exeSuffix();
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

        $platform = match ([$os, $arch]) {
            ['darwin', 'arm64'] => 'macos-arm64',
            ['darwin', 'x64']   => 'macos-x64',
            ['linux',  'arm64'] => 'linux-arm64',
            ['linux',  'x64']   => 'linux-x64',
            ['win32',  'x64']   => 'windows-x64.exe',
            default => throw new RuntimeException("No Tailwind binary for {$os}-{$arch}"),
        };

        $url  = "https://github.com/tailwindlabs/tailwindcss/releases/download/v{$this->version}/tailwindcss-{$platform}";
        $dest = $this->path();

        if (!is_dir($this->binDir)) {
            mkdir($this->binDir, 0777, true);
        }

        $this->downloader->download($url, $dest);
        chmod($dest, 0755);

        return $dest;
    }

    public function run(array $args, ?string $cwd = null): array
    {
        $bin = $this->ensureInstalled();
        $cmd = array_merge([$bin], $args);

        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $cwd);
        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start tailwindcss.');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exit = proc_close($process);
        return [$exit, $stdout, $stderr];
    }
}
