<?php

namespace Genericmilk\Cooker\Toolbox;

class Toolbox
{
    public function __construct(
        protected string $basePath,
        protected array $config,
    ) {
    }

    public function binDir(): string
    {
        $rel = $this->config['path'] ?? '.cooker/bin';
        return $this->basePath.'/'.$rel;
    }

    public function esbuild(): Esbuild
    {
        return new Esbuild($this->binDir(), $this->config['esbuild'] ?? '0.24.2');
    }

    public function tailwind(): Tailwind
    {
        return new Tailwind($this->binDir(), $this->config['tailwind'] ?? '4.0.0');
    }
}
