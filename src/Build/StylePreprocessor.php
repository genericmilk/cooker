<?php

namespace Genericmilk\Cooker\Build;

use Genericmilk\Cooker\Toolbox\Tailwind;
use Less_Parser;
use RuntimeException;
use ScssPhp\ScssPhp\Compiler;

class StylePreprocessor
{
    public function __construct(
        protected ?Tailwind $tailwind = null,
    ) {
    }

    /**
     * If the entry is .less or .scss, compile it to CSS in $cacheDir and
     * return the new path. If the resulting CSS uses Tailwind, run it
     * through the Tailwind binary before returning.
     */
    public function preprocess(string $entryAbsolute, string $cacheDir): string
    {
        $ext = strtolower(pathinfo($entryAbsolute, PATHINFO_EXTENSION));

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $css = match ($ext) {
            'css'  => $entryAbsolute,
            'less' => $this->less($entryAbsolute, $cacheDir.'/'.basename($entryAbsolute, '.less').'.css'),
            'scss' => $this->scss($entryAbsolute, $cacheDir.'/'.basename($entryAbsolute, '.scss').'.css'),
            default => throw new RuntimeException("Unsupported style format: $ext"),
        };

        if ($this->usesTailwind($css)) {
            $css = $this->tailwind($css, $cacheDir.'/tw-'.basename($css));
        }

        return $css;
    }

    protected function less(string $in, string $out): string
    {
        $parser = new Less_Parser(['compress' => false]);
        $parser->parseFile($in);
        file_put_contents($out, $parser->getCss());
        return $out;
    }

    protected function scss(string $in, string $out): string
    {
        $compiler = new Compiler();
        $compiler->setImportPaths(dirname($in));
        $css = $compiler->compileString(file_get_contents($in), $in)->getCss();
        file_put_contents($out, $css);
        return $out;
    }

    protected function usesTailwind(string $cssPath): bool
    {
        if (!is_file($cssPath)) {
            return false;
        }
        $head = (string) file_get_contents($cssPath, false, null, 0, 4096);
        return str_contains($head, '@import "tailwindcss"')
            || str_contains($head, "@import 'tailwindcss'")
            || str_contains($head, '@tailwind ');
    }

    protected function tailwind(string $in, string $out): string
    {
        if (!$this->tailwind) {
            throw new RuntimeException('Tailwind binary unavailable. Run `php artisan cooker:add tailwind`.');
        }

        [$exit, $stdout, $stderr] = $this->tailwind->run([
            '-i', $in,
            '-o', $out,
        ]);

        if ($exit !== 0) {
            throw new RuntimeException("tailwindcss failed:\n".$stderr.$stdout);
        }

        return $out;
    }
}
