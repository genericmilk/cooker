<?php

namespace Genericmilk\Cooker\Stacks;

use Genericmilk\Cooker\Cooker;

abstract class Stack
{
    public function __construct(protected Cooker $cooker)
    {
    }

    abstract public function name(): string;

    abstract public function description(): string;

    /**
     * npm packages this stack installs.
     * @return array<string, string>
     */
    abstract public function packages(): array;

    /**
     * Files to scaffold relative to base_path. Source files come from src/Defaults/stacks/{name}.
     * Returning ['target' => 'source'].
     */
    abstract public function files(): array;

    /**
     * Apply scaffolding. Default: copy bundled stack files, install packages.
     */
    public function install(callable $log): void
    {
        $log("Installing {$this->name()} stack");

        foreach ($this->files() as $target => $source) {
            $this->scaffold($target, $source, $log);
        }

        $installer = $this->cooker->installer();
        foreach ($this->packages() as $pkg => $range) {
            $log("  → cooker:add {$pkg}@{$range}");
            $installer->install($pkg, $range);
        }

        $manifest = $this->cooker->cookerJson();
        $manifest->addStack($this->name());
        $manifest->save();
    }

    public function uninstall(callable $log): void
    {
        $manifest = $this->cooker->cookerJson();
        $manifest->removeStack($this->name());
        $manifest->save();

        $installer = $this->cooker->installer();
        foreach (array_keys($this->packages()) as $pkg) {
            $log("  → removing {$pkg}");
            $installer->remove($pkg);
        }
    }

    protected function scaffold(string $target, string $source, callable $log): void
    {
        $sourcePath = __DIR__.'/../Defaults/stacks/'.$this->name().'/'.$source;
        $targetPath = $this->cooker->basePath($target);

        if (!is_file($sourcePath)) {
            $log("  ! source missing: $source");
            return;
        }

        if (is_file($targetPath)) {
            $log("  · skipped (exists) $target");
            return;
        }

        if (!is_dir(dirname($targetPath))) {
            mkdir(dirname($targetPath), 0777, true);
        }

        copy($sourcePath, $targetPath);
        $log("  + wrote $target");
    }
}
