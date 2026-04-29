<?php

namespace Genericmilk\Cooker\Build;

class Watcher
{
    /** @var array<string, int> */
    protected array $signatures = [];

    public function __construct(
        protected Builder $builder,
        protected ?DevServer $devServer = null,
        protected int $intervalMs = 400,
    ) {
    }

    public function devServer(): ?DevServer
    {
        return $this->devServer;
    }

    public function run(?callable $shouldStop = null, ?callable $onBuild = null): void
    {
        $this->builder->buildOnce();
        $this->seedSignatures();
        if ($onBuild) {
            $onBuild('initial');
        }

        if ($this->devServer) {
            $this->devServer->start();
        }

        while (true) {
            if ($shouldStop && $shouldStop()) {
                break;
            }

            $this->devServer?->tick();

            $changed = $this->detectChangedRecipes();

            if ($changed) {
                try {
                    $hashes = $this->rebuildSubset($changed);
                    $this->saveManifest($hashes);

                    $allCss = array_reduce($changed, fn ($carry, Recipe $r) => $carry && $r->isStyle(), true);

                    if ($this->devServer) {
                        $changes = array_map(fn (Recipe $r) => [
                            'output' => $r->output,
                            'hashed' => $hashes[$r->output] ?? null,
                            'type'   => $r->isStyle() ? 'css' : 'js',
                        ], $changed);

                        $this->devServer->notify($allCss ? 'css-update' : 'reload', [
                            'changes' => $changes,
                        ]);
                    }

                    if ($onBuild) {
                        $onBuild($allCss ? 'css-update' : 'rebuild', null, $changed);
                    }
                } catch (\Throwable $e) {
                    $this->devServer?->notify('error', ['message' => $e->getMessage()]);
                    if ($onBuild) {
                        $onBuild('error', $e);
                    }
                }
            }

            usleep($this->intervalMs * 1000);
        }

        $this->devServer?->stop();
    }

    protected function seedSignatures(): void
    {
        foreach ($this->builder->recipes() as $recipe) {
            $this->signatures[$recipe->output] = $this->treeSignature(dirname($recipe->entryAbsolute));
        }
    }

    /**
     * @return array<int, Recipe>
     */
    protected function detectChangedRecipes(): array
    {
        $changed = [];
        foreach ($this->builder->recipes() as $recipe) {
            $sig = $this->treeSignature(dirname($recipe->entryAbsolute));
            if (($this->signatures[$recipe->output] ?? null) !== $sig) {
                $this->signatures[$recipe->output] = $sig;
                $changed[] = $recipe;
            }
        }
        return $changed;
    }

    /**
     * @param array<int, Recipe> $recipes
     * @return array<string, string>
     */
    protected function rebuildSubset(array $recipes): array
    {
        $config = $this->builder->config();
        $minify = false;
        $sourcemap = true;
        $target = $config['build']['target'] ?? 'es2020';

        $hashes = [];
        foreach ($recipes as $recipe) {
            $hashes[$recipe->output] = $this->builder->buildOne($recipe, $minify, $sourcemap, $target);
        }
        return $hashes;
    }

    protected function saveManifest(array $hashes): void
    {
        $manifest = new Manifest($this->builder->outputDir().'/manifest.json');
        foreach ($hashes as $output => $hashed) {
            $manifest->set($output, $hashed);
        }
        $manifest->save();
    }

    protected function treeSignature(string $dir): int
    {
        if (!is_dir($dir)) {
            return 0;
        }
        $sum = 0;
        $it  = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile()) {
                $sum += $f->getMTime();
            }
        }
        return $sum;
    }
}
