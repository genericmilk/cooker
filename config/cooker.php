<?php
/*
 * Cooker 7
 *
 * (c) Peter Day (genericmilk) <peterday.main@gmail.com> 2024-2025
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Silent
    |--------------------------------------------------------------------------
    |
    | Only output to the console if there has been an error during cooking
    |
    */
    'silent' => false,

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Fire a desktop notification after Cooker completes
    |
    */
    'notifications' => env('APP_DEBUG', true),

    /*
    |--------------------------------------------------------------------------
    | Can Speedy Cook
    |--------------------------------------------------------------------------
    |
    | Whether or not Cooker can cook in Speedy mode. Speedy mode allows Cooker
    | to cook faster by only compiling the files that have changed rather than
    | compiling everything. This is useful for development but not for production.
    |
    */
    'canSpeedyCook' => env('COOKER_CAN_SPEEDY_COOK', true),

    /*
    |--------------------------------------------------------------------------
    | Cooker Package Manager
    |--------------------------------------------------------------------------
    |
    | The settings for the Cooker Package Manager. This is used to install
    | and update packages from NPM-like repositories.
    |
    */
    'packageManager' => [
        'packagesList' => env('COOKER_PACKAGE_JSON_LOCATION', base_path('cooker.json')),
        'packagesPath' => env('COOKER_PACKAGE_PATH', base_path('cooker_packages')),
        'packageManager' => env('COOKER_PACKAGE_MANAGER', 'jsdelivr'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Run Intelli Path
    |--------------------------------------------------------------------------
    |
    | Cooker Toolbelt features a function that automatically runs intelliPath, a system
    | that looks at your page URL and automatically runs the boot function for matching paths.
    | If you would rather call the cookerToolbelt.intelliPath(); function manually then set this
    | to false. This will allow you to run the function after you have loaded any prerequisites for
    | your application
    |
    */
    'autoRunIntelliPath' => env('COOKER_AUTO_RUN_INTELLI_PATH', true),

    /*
    |--------------------------------------------------------------------------
    | Frameworks
    |--------------------------------------------------------------------------
    |
    | A list of frameworks used in all javascript files. The currently
    | supported values are 'vue', 'react', 'angular', 'jquery', 'tailwind'
    |
    */
    'frameworks' => [],

    /*
    |--------------------------------------------------------------------------
    | Ovens
    |--------------------------------------------------------------------------
    |
    | The main settings for the ovens. Each oven is an array with the following
    | settings: cooker, preload, input, output, name, stamped, toolbelt
    | For more information on each setting, see the documentation
    |
    */
    'ovens' => [
        [
            'cooker' => 'Genericmilk\Cooker\Ovens\Less',
            'preload' => [
                /*
                    Place any CDNs or local paths in the array here.
                    These files will be injected and not parsed before the appcode
                    These entires can be either a string for both dev and prod or an array with dev and prod keys
                    For example

                    'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css',

                    OR

                    [
                        'dev' => 'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css',
                        'prod' => 'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css'
                    ]
                */
            ],
            'input' => [
                'app.less'
            ],
            'output' => 'app.css',
            'name' => 'Example Css',
            'stamped' => true
        ],
        [
            'cooker' => 'Genericmilk\Cooker\Ovens\Js',
            'preload' => [
                /*
                    Place any CDNs or local paths in the array here.
                    These files will be injected and not parsed before the appcode
                    These entires can be either a string for both dev and prod or an array with dev and prod keys
                    For example

                    'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css',

                    OR

                    [
                        'dev' => 'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css',
                        'prod' => 'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css'
                    ]
                */
            ],
            'input' => [
                'app.js'
            ],
            'namespace' => 'app',
            'output' => 'app.js',
            'name' => 'Example Javascript',
            'stamped' => true,
            'toolbelt' => true
        ]
    ]

];
