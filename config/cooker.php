<?php
/*
 * Cooker 10
 *
 * (c) Peter Day (genericmilk) <peterday.main@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Recipes
    |--------------------------------------------------------------------------
    |
    | Each recipe maps an output filename (the value used in @cooker(...)) to
    | an entrypoint file inside resources/. Cooker auto-detects the format
    | from the extension. Add as many recipes as you like.
    |
    */
    'recipes' => [
        'app.js'  => 'resources/js/app.js',
        'app.css' => 'resources/css/app.css',
    ],

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    |
    | Where built assets are written and how the @cooker directive references
    | them. `path` is filesystem-relative to base_path(). `url` is the public
    | URL prefix used in generated <script>/<link> tags.
    |
    */
    'output' => [
        'path' => 'public/build',
        'url'  => '/build',
    ],

    /*
    |--------------------------------------------------------------------------
    | Toolbox
    |--------------------------------------------------------------------------
    |
    | Cooker auto-downloads the binaries it needs (esbuild for bundling,
    | tailwindcss for utility CSS) into .cooker/bin. Pin versions here.
    |
    */
    'toolbox' => [
        'path'      => '.cooker/bin',
        'esbuild'   => '0.24.2',
        'tailwind'  => '4.0.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Packages
    |--------------------------------------------------------------------------
    |
    | npm-compatible packages installed via `cooker:add` are extracted to
    | this folder. esbuild resolves bare imports against it. The list of
    | installed packages lives in .cooker/cooker.json.
    |
    */
    'packages' => [
        'path'     => '.cooker/packages',
        'manifest' => '.cooker/cooker.json',
        'registry' => env('COOKER_REGISTRY', 'https://registry.npmjs.org'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dev server (live reload)
    |--------------------------------------------------------------------------
    |
    | When `cooker:watch` is running, Cooker hosts a tiny SSE server. The
    | @cooker Blade directive injects a small client snippet into your pages
    | (only when app.debug=true) that connects here and reloads on rebuild.
    | CSS-only changes hot-swap stylesheets without a full reload.
    |
    */
    'dev' => [
        'enabled' => env('COOKER_DEV_ENABLED', null),
        'host'    => env('COOKER_DEV_HOST', '127.0.0.1'),
        'port'    => env('COOKER_DEV_PORT', 5173),
    ],

    /*
    |--------------------------------------------------------------------------
    | Build options
    |--------------------------------------------------------------------------
    |
    | `minify` and `sourcemap` default to the inverse of app.debug. Override
    | with COOKER_MINIFY / COOKER_SOURCEMAP env vars if you need to.
    |
    */
    'build' => [
        'minify'    => env('COOKER_MINIFY', null),
        'sourcemap' => env('COOKER_SOURCEMAP', null),
        'target'    => env('COOKER_TARGET', 'es2020'),
    ],

];
