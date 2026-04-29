<?php

namespace Genericmilk\Cooker\Packages;

use Genericmilk\Cooker\Toolbox\BinaryDownloader;
use PharData;
use RuntimeException;

class Installer
{
    public function __construct(
        protected string $packagesDir,
        protected Registry $registry,
        protected CookerJson $manifest,
        protected ?BinaryDownloader $downloader = null,
    ) {
        $this->downloader ??= new BinaryDownloader();
    }

    /**
     * Install a package and its transitive deps. Returns names installed.
     */
    public function install(string $name, string $range = 'latest'): array
    {
        $installed = [];
        $this->installRecursive($name, $range, $installed);

        if (isset($installed[$name])) {
            $this->manifest->setPackage($name, $installed[$name]);
            $this->manifest->save();
        }

        return array_keys($installed);
    }

    public function remove(string $name): void
    {
        $dir = $this->packagesDir.'/'.$name;
        if (is_dir($dir)) {
            $this->rrmdir($dir);
        }
        $this->manifest->removePackage($name);
        $this->manifest->save();
    }

    protected function installRecursive(string $name, string $range, array &$installed): void
    {
        $resolved = $this->registry->resolve($name, $range);
        $version  = $resolved['version'];

        if (isset($installed[$name]) && $installed[$name] === $version) {
            return;
        }

        $target = $this->packagesDir.'/'.$name;
        if (!is_dir($target) || !$this->isVersion($target, $version)) {
            $this->extractPackage($resolved, $target);
        }

        $installed[$name] = $version;

        foreach ($resolved['dependencies'] as $dep => $depRange) {
            if (isset($installed[$dep])) {
                continue;
            }
            $this->installRecursive($dep, $depRange, $installed);
        }
    }

    protected function extractPackage(array $resolved, string $target): void
    {
        if (empty($resolved['tarball'])) {
            throw new RuntimeException("No tarball for {$resolved['name']}@{$resolved['version']}");
        }

        if (!is_dir($this->packagesDir)) {
            mkdir($this->packagesDir, 0777, true);
        }

        $tmpDir = $this->packagesDir.'/.dl-'.bin2hex(random_bytes(4));
        mkdir($tmpDir, 0777, true);

        $tgz = $tmpDir.'/pkg.tgz';
        $tar = $tmpDir.'/pkg.tar';
        $this->downloader->download($resolved['tarball'], $tgz);

        $in  = gzopen($tgz, 'rb');
        $out = fopen($tar, 'wb');
        if ($in === false || $out === false) {
            throw new RuntimeException('Could not open tarball stream.');
        }
        while (!gzeof($in)) {
            fwrite($out, gzread($in, 65536));
        }
        gzclose($in);
        fclose($out);

        $phar = new PharData($tar);
        $phar->extractTo($tmpDir, null, true);

        if ($this->isDir($target)) {
            $this->rrmdir($target);
        }
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        rename($tmpDir.'/package', $target);
        $this->writeVersion($target, $resolved['version']);
        $this->rrmdir($tmpDir);
    }

    protected function isVersion(string $dir, string $version): bool
    {
        $stamp = $dir.'/.cooker-version';
        return is_file($stamp) && trim((string) file_get_contents($stamp)) === $version;
    }

    protected function writeVersion(string $dir, string $version): void
    {
        file_put_contents($dir.'/.cooker-version', $version);
    }

    protected function isDir(string $path): bool
    {
        return is_dir($path);
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
