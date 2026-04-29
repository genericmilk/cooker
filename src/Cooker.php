<?php

namespace Genericmilk\Cooker;

use Genericmilk\Cooker\Build\Builder;
use Genericmilk\Cooker\Build\DevServer;
use Genericmilk\Cooker\Build\Manifest;
use Genericmilk\Cooker\Build\Watcher;
use Genericmilk\Cooker\Packages\CookerJson;
use Genericmilk\Cooker\Packages\Installer;
use Genericmilk\Cooker\Packages\Registry;
use Genericmilk\Cooker\Toolbox\Toolbox;

class Cooker
{
    public function __construct(
        protected string $basePath,
        protected array $config,
    ) {
    }

    public static function fromLaravel(): self
    {
        return new self(base_path(), config('cooker', []));
    }

    public function basePath(string $append = ''): string
    {
        return $append === '' ? $this->basePath : $this->basePath.'/'.ltrim($append, '/');
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }
        $value = $this->config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function toolbox(): Toolbox
    {
        return new Toolbox($this->basePath, $this->config['toolbox'] ?? []);
    }

    public function builder(): Builder
    {
        $toolbox = $this->toolbox();
        $tailwind = in_array('tailwind', $this->cookerJson()->stacks(), true)
            ? $toolbox->tailwind()
            : null;

        return new Builder($this->basePath, $this->config, $toolbox->esbuild(), $tailwind);
    }

    public function watcher(): Watcher
    {
        return new Watcher($this->builder(), $this->devServer());
    }

    public function devServer(): DevServer
    {
        return new DevServer(
            (string) ($this->config['dev']['host'] ?? '127.0.0.1'),
            (int)    ($this->config['dev']['port'] ?? 5173),
        );
    }

    public function devServerUrl(): string
    {
        $host = $this->config['dev']['host'] ?? '127.0.0.1';
        $port = $this->config['dev']['port'] ?? 5173;
        return "http://{$host}:{$port}/__cooker-hmr";
    }

    public function devEnabled(): bool
    {
        $explicit = $this->config['dev']['enabled'] ?? null;
        if ($explicit !== null) {
            return (bool) $explicit;
        }
        return (bool) config('app.debug');
    }

    public function manifest(): Manifest
    {
        return new Manifest($this->builder()->outputDir().'/manifest.json');
    }

    public function cookerJson(): CookerJson
    {
        $rel = $this->config['packages']['manifest'] ?? '.cooker/cooker.json';
        return new CookerJson($this->basePath.'/'.$rel);
    }

    public function installer(): Installer
    {
        $registry = new Registry($this->config['packages']['registry'] ?? 'https://registry.npmjs.org');
        $packages = $this->basePath.'/'.ltrim($this->config['packages']['path'] ?? '.cooker/packages', '/');
        return new Installer($packages, $registry, $this->cookerJson());
    }

    public function asset(string $name): ?string
    {
        $hashed = $this->manifest()->get($name);
        if ($hashed === null) {
            return null;
        }
        $url = rtrim($this->config['output']['url'] ?? '/build', '/');
        return $url.'/'.$hashed;
    }
}
