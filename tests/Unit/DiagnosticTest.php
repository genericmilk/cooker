<?php

namespace Genericmilk\Cooker\Tests\Unit;

use Genericmilk\Cooker\Build\Diagnostic;

test('Diagnostic suggests cooker:add for missing bare imports', function () {
    $output = '✘ [ERROR] Could not resolve "lodash"';
    [$summary, $hints] = Diagnostic::fromEsbuild($output, 'app.js');
    expect($summary)->toContain('lodash');
    expect(implode("\n", $hints))->toContain('cooker:add lodash');
});

test('Diagnostic strips subpaths when suggesting installs', function () {
    [$_, $hints] = Diagnostic::fromEsbuild('Could not resolve "lodash/fp"', 'app.js');
    expect(implode("\n", $hints))->toContain('cooker:add lodash');

    [$_, $hints] = Diagnostic::fromEsbuild('Could not resolve "@scope/pkg/sub/path"', 'app.js');
    expect(implode("\n", $hints))->toContain('cooker:add @scope/pkg');
});

test('Diagnostic detects missing entry files', function () {
    [$summary, $hints] = Diagnostic::fromEsbuild(
        '✘ [ERROR] Could not read from file: resources/js/app.js',
        'app.js',
    );
    expect($summary)->toContain('find a file');
    expect(implode("\n", $hints))->toContain('config/cooker.php');
});

test('Diagnostic flags invalid build flags as a Cooker bug', function () {
    [$summary, $hints] = Diagnostic::fromEsbuild('✘ [ERROR] Invalid build flag: "--foo"', 'app.js');
    expect($summary)->toContain('unrecognised flag');
    expect(implode("\n", $hints))->toContain('github.com');
});
