<?php

namespace Genericmilk\Cooker\Build;

use Genericmilk\Cooker\Toolbox\Esbuild;
use Genericmilk\Cooker\Toolbox\Tailwind;
use RuntimeException;

class Builder
{
    public function __construct(
        protected string $basePath,
        protected array $config,
        protected Esbuild $esbuild,
        protected ?Tailwind $tailwind = null,
        protected ?StylePreprocessor $preprocessor = null,
    ) {
        $this->preprocessor ??= new StylePreprocessor($tailwind);
    }

    public function config(): array
    {
        return $this->config;
    }

    public function recipes(): array
    {
        $recipes = [];
        foreach ($this->config['recipes'] ?? [] as $output => $entry) {
            $abs = $this->basePath.'/'.ltrim($entry, '/');
            $recipes[] = new Recipe($output, $entry, $abs);
        }
        return $recipes;
    }

    public function outputDir(): string
    {
        return $this->basePath.'/'.ltrim($this->config['output']['path'] ?? 'public/build', '/');
    }

    public function packagesDir(): string
    {
        return $this->basePath.'/'.ltrim($this->config['packages']['path'] ?? '.cooker/packages', '/');
    }

    public function cacheDir(): string
    {
        return $this->basePath.'/.cooker/cache';
    }

    public function buildAll(bool $minify, bool $sourcemap, string $target): Manifest
    {
        $outDir   = $this->outputDir();
        $manifest = new Manifest($outDir.'/manifest.json');

        if (!is_dir($outDir)) {
            mkdir($outDir, 0777, true);
        }

        foreach ($this->recipes() as $recipe) {
            $hashed = $this->buildOne($recipe, $minify, $sourcemap, $target);
            $manifest->set($recipe->output, $hashed);
        }

        $manifest->save();
        return $manifest;
    }

    public function buildOne(Recipe $recipe, bool $minify, bool $sourcemap, string $target): string
    {
        if (!is_file($recipe->entryAbsolute)) {
            throw new BuildException(
                summary: "Cooker can't find the entry file for `{$recipe->output}`.",
                rawOutput: '',
                hints: [
                    "Expected file:  {$recipe->entry}",
                    'Update the path in `recipes` inside config/cooker.php, or create the file.',
                    'If you just installed Cooker, run `php artisan cooker:install` to scaffold the defaults.',
                ],
                recipe: $recipe->output,
            );
        }

        $entry = $recipe->entryAbsolute;
        if ($recipe->isStyle()) {
            try {
                $entry = $this->preprocessor->preprocess($entry, $this->cacheDir().'/styles');
            } catch (\Throwable $e) {
                throw new BuildException(
                    summary: "Style preprocessing failed for `{$recipe->output}`: ".$e->getMessage(),
                    rawOutput: $e->getMessage(),
                    hints: [
                        '.less files require valid LESS syntax; .scss files require valid SCSS syntax.',
                        'If you meant to use Tailwind, ensure your CSS imports it via `@import "tailwindcss";` and the tailwind stack is installed (php artisan cooker:add tailwind).',
                    ],
                    recipe: $recipe->output,
                );
            }
        }

        $hash       = $this->shortHash($recipe->entry.'|'.filemtime($entry));
        $outputName = $this->hashedName($recipe->output, $hash);
        $outputPath = $this->outputDir().'/'.$outputName;

        $args = [
            $entry,
            '--bundle',
            '--outfile='.$outputPath,
            '--target='.$target,
            '--loader:.png=file',
            '--loader:.jpg=file',
            '--loader:.jpeg=file',
            '--loader:.gif=file',
            '--loader:.svg=file',
            '--loader:.woff=file',
            '--loader:.woff2=file',
            '--loader:.ttf=file',
            '--loader:.eot=file',
            '--asset-names=[name]-[hash]',
            '--public-path='.($this->config['output']['url'] ?? '/build'),
            '--log-level=warning',
        ];

        if ($recipe->isScript()) {
            $args[] = '--format=esm';
            $args[] = '--jsx=automatic';
            $args[] = '--define:__VUE_OPTIONS_API__=true';
            $args[] = '--define:__VUE_PROD_DEVTOOLS__=false';
            $args[] = '--define:__VUE_PROD_HYDRATION_MISMATCH_DETAILS__=false';
            $args[] = '--define:process.env.NODE_ENV='.($minify ? '"production"' : '"development"');
        }

        if ($minify) {
            $args[] = '--minify';
        }

        if ($sourcemap) {
            $args[] = '--sourcemap';
        }

        $env = ['NODE_PATH' => $this->packagesDir()];

        [$exit, $stdout, $stderr] = $this->esbuild->run($args, $this->basePath, $env);

        if ($exit !== 0) {
            $output = trim($stderr.$stdout);
            [$summary, $hints] = Diagnostic::fromEsbuild($output, $recipe->output);
            throw new BuildException(
                summary: $summary,
                rawOutput: $output,
                hints: $hints,
                recipe: $recipe->output,
            );
        }

        return $outputName;
    }

    public function buildOnce(): Manifest
    {
        $minify    = $this->resolveBool('minify',    !config('app.debug'));
        $sourcemap = $this->resolveBool('sourcemap', (bool) config('app.debug'));
        $target    = $this->config['build']['target'] ?? 'es2020';

        return $this->buildAll($minify, $sourcemap, $target);
    }

    public function watch(): void
    {
        $args = ['--watch'];
        $minify    = $this->resolveBool('minify',    false);
        $sourcemap = $this->resolveBool('sourcemap', true);
        $target    = $this->config['build']['target'] ?? 'es2020';

        foreach ($this->recipes() as $recipe) {
            $this->buildOne($recipe, $minify, $sourcemap, $target);
        }
    }

    protected function resolveBool(string $key, bool $default): bool
    {
        $val = $this->config['build'][$key] ?? null;
        return $val === null ? $default : (bool) $val;
    }

    protected function hashedName(string $output, string $hash): string
    {
        $ext  = pathinfo($output, PATHINFO_EXTENSION);
        $stem = pathinfo($output, PATHINFO_FILENAME);
        return $stem.'-'.$hash.'.'.$ext;
    }

    protected function shortHash(string $input): string
    {
        return substr(hash('xxh128', $input), 0, 10);
    }
}
