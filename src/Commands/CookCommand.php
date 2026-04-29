<?php

namespace Genericmilk\Cooker\Commands;

use Genericmilk\Cooker\Build\BuildException;
use Genericmilk\Cooker\Cooker;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;

class CookCommand extends Command
{
    protected $signature = 'cooker:cook
        {--no-minify : Skip minification (overrides config)}
        {--sourcemap : Emit sourcemaps}
        {--clean : Wipe public/build before building}
        {--verbose-errors : Show full esbuild output even when Cooker has a friendly summary}';

    protected $description = 'Build all recipes into public/build with hashed filenames.';

    public function handle(Cooker $cooker): int
    {
        note('👨‍🍳 Cooker — building');

        $builder = $cooker->builder();
        $outDir  = $builder->outputDir();

        if ($this->option('clean') && is_dir($outDir)) {
            foreach (glob($outDir.'/*') ?: [] as $f) {
                if (is_file($f)) {
                    unlink($f);
                }
            }
        }

        $minify    = !$this->option('no-minify') && !config('app.debug');
        $sourcemap = (bool) $this->option('sourcemap') || (bool) config('app.debug');
        $target    = (string) ($cooker->config('build.target') ?? 'es2020');

        try {
            $manifest = spin(
                fn () => $builder->buildAll($minify, $sourcemap, $target),
                'Cooking recipes',
            );
        } catch (BuildException $e) {
            $this->renderBuildException($e);
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->renderUnknownError($e);
            return self::FAILURE;
        }

        foreach ($manifest->all() as $logical => $hashed) {
            info("✔ {$logical} → {$hashed}");
        }

        return self::SUCCESS;
    }

    protected function renderBuildException(BuildException $e): void
    {
        $this->newLine();
        $this->components->error($e->summary);

        if ($e->recipe) {
            $this->components->twoColumnDetail('Recipe', $e->recipe);
        }

        if ($e->hints) {
            $this->newLine();
            $this->line('  <fg=yellow;options=bold>How to fix this:</>');
            foreach ($e->hints as $hint) {
                $this->line('    <fg=yellow>•</> '.$hint);
            }
        }

        if ($e->rawOutput && $this->option('verbose-errors')) {
            $this->newLine();
            $this->line('  <fg=gray>esbuild output:</>');
            foreach (explode("\n", rtrim($e->rawOutput)) as $line) {
                $this->line('  <fg=gray>'.$line.'</>');
            }
        } elseif ($e->rawOutput) {
            $this->newLine();
            $this->line('  <fg=gray>Run with --verbose-errors to see full esbuild output.</>');
        }

        $this->newLine();
    }

    protected function renderUnknownError(\Throwable $e): void
    {
        $this->newLine();
        $this->components->error('Cooker hit an unexpected error: '.$e->getMessage());
        $this->line('  <fg=yellow>•</> If this looks like a Cooker bug, please open an issue at https://github.com/genericmilk/cooker/issues');
        $this->newLine();
    }
}
