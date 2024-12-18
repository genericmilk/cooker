<?php
/*
 * Cooker 8
 *
 * (c) Peter Day (genericmilk) <peterday.main@gmail.com> 2025-2026
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
return [

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
            'startupClass' => 'Application', 
            'output' => 'app.js',
        ]
    ],

    'hot_reload' => [
        'enabled' => true,
        'port' => 3000,
        'host' => 'localhost'
    ],

];
