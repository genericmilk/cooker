<?php

namespace Genericmilk\Cooker\Toolbox;

use RuntimeException;

class Platform
{
    public static function os(): string
    {
        return match (true) {
            stripos(PHP_OS, 'darwin') === 0 => 'darwin',
            stripos(PHP_OS, 'linux')  === 0 => 'linux',
            stripos(PHP_OS, 'win')    === 0 => 'win32',
            default => throw new RuntimeException('Unsupported OS: '.PHP_OS),
        };
    }

    public static function arch(): string
    {
        $m = strtolower(php_uname('m'));
        return match (true) {
            in_array($m, ['arm64', 'aarch64'], true) => 'arm64',
            in_array($m, ['x86_64', 'amd64'], true)  => 'x64',
            default => throw new RuntimeException('Unsupported architecture: '.$m),
        };
    }

    public static function isWindows(): bool
    {
        return self::os() === 'win32';
    }

    public static function exeSuffix(): string
    {
        return self::isWindows() ? '.exe' : '';
    }
}
