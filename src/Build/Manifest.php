<?php

namespace Genericmilk\Cooker\Build;

class Manifest
{
    protected array $entries = [];

    public function __construct(protected string $path)
    {
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                $this->entries = $decoded;
            }
        }
    }

    public function set(string $name, string $hashedFile): void
    {
        $this->entries[$name] = $hashedFile;
    }

    public function get(string $name): ?string
    {
        return $this->entries[$name] ?? null;
    }

    public function all(): array
    {
        return $this->entries;
    }

    public function save(): void
    {
        if (!is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0777, true);
        }
        file_put_contents(
            $this->path,
            json_encode($this->entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );
    }
}
