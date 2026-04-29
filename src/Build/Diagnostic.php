<?php

namespace Genericmilk\Cooker\Build;

class Diagnostic
{
    /**
     * Inspect raw esbuild output and return [summary, hints[]].
     *
     * @return array{0: string, 1: array<int, string>}
     */
    public static function fromEsbuild(string $output, string $recipe): array
    {
        $hints = [];
        $summary = "Cooker couldn't build {$recipe}.";

        if (preg_match_all('/Could not resolve "([^"]+)"/', $output, $matches)) {
            $missing = array_unique($matches[1]);
            $bare    = array_filter($missing, fn ($m) => !str_starts_with($m, '.') && !str_starts_with($m, '/'));
            $relative = array_diff($missing, $bare);

            if ($bare) {
                $summary = 'Missing npm package'.(count($bare) > 1 ? 's' : '').': '.implode(', ', $bare);
                foreach ($bare as $pkg) {
                    $top = self::topLevelPackage($pkg);
                    $hints[] = "Install it with:  php artisan cooker:add {$top}";
                }
                $hints[] = "Cooker stores packages under .cooker/packages — there's no package.json or node_modules to manage by hand.";
            }
            if ($relative) {
                $summary = 'Could not resolve relative import: '.implode(', ', $relative);
                $hints[] = 'Check the import path in your entry file. Cooker resolves relative imports from the file doing the import.';
            }
        }

        if (str_contains($output, 'Could not read from file:') || str_contains($output, 'no such file or directory')) {
            $summary = "Cooker couldn't find a file for {$recipe}.";
            $hints[] = 'Confirm the entry exists and the path in `recipes` (config/cooker.php) is correct.';
            $hints[] = 'If you just installed Cooker, the default entries live at resources/js/app.js and resources/css/app.css.';
        }

        if (preg_match('/Invalid build flag/', $output)) {
            $summary = 'Cooker passed an unrecognised flag to esbuild.';
            $hints[] = 'This is a Cooker bug — please report it at https://github.com/genericmilk/cooker/issues with the output above.';
            $hints[] = 'You can pin a known-good esbuild version in config/cooker.php under `toolbox.esbuild` while we fix it.';
        }

        if ($hints === []) {
            $hints[] = 'See the esbuild output above for details.';
            $hints[] = 'If you think this is a Cooker bug, open an issue with the output: https://github.com/genericmilk/cooker/issues';
        }

        return [$summary, $hints];
    }

    /**
     * Given an import like 'lodash/fp' or '@scope/pkg/sub', return the npm package name.
     */
    public static function topLevelPackage(string $importSpecifier): string
    {
        if (str_starts_with($importSpecifier, '@')) {
            $parts = explode('/', $importSpecifier, 3);
            return implode('/', array_slice($parts, 0, 2));
        }
        return explode('/', $importSpecifier, 2)[0];
    }
}
