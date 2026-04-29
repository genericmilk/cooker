<?php

namespace Genericmilk\Cooker\Packages;

class CookerJson
{
    protected array $data = [];

    public function __construct(protected string $path)
    {
        $this->load();
    }

    protected function load(): void
    {
        if (is_file($this->path)) {
            $decoded = json_decode((string) file_get_contents($this->path), true);
            if (is_array($decoded)) {
                $this->data = $decoded;
            }
        }

        $this->data += [
            'name'     => basename(dirname($this->path, 2)),
            'cooker'   => '10',
            'packages' => [],
            'stacks'   => [],
        ];
    }

    public function packages(): array
    {
        return $this->data['packages'] ?? [];
    }

    public function setPackage(string $name, string $version): void
    {
        $this->data['packages'][$name] = $version;
        ksort($this->data['packages']);
    }

    public function removePackage(string $name): void
    {
        unset($this->data['packages'][$name]);
    }

    public function stacks(): array
    {
        return $this->data['stacks'] ?? [];
    }

    public function addStack(string $name): void
    {
        $stacks = $this->data['stacks'] ?? [];
        if (!in_array($name, $stacks, true)) {
            $stacks[] = $name;
        }
        sort($stacks);
        $this->data['stacks'] = $stacks;
    }

    public function removeStack(string $name): void
    {
        $this->data['stacks'] = array_values(array_filter(
            $this->data['stacks'] ?? [],
            fn ($s) => $s !== $name,
        ));
    }

    public function save(): void
    {
        if (!is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0777, true);
        }
        file_put_contents(
            $this->path,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );
    }

    public function raw(): array
    {
        return $this->data;
    }
}
