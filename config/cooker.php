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
            'file' => 'app.less',
            'components' => [
                'parse' => [
                    'app.less'
                ],                
            ],
        ],
        [
            'file' => 'app.js',

            'components' => [
                'parse' => [
                    'app.js'
                ],
                'routes' => [
                    [
                        'path' => '*',
                        'class' => 'Application',
                    ]
                ]
            ],
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    |
    | The main settings for the cooker. Use these settings to configure the
    | behaviour of how Cooker works. For more information on each setting, see
    | the documentation
    |
    */

    'options' => [
        'disableCache' => env('COOKER_DISABLE_CACHE', false),
        'alwaysCompress' => env('COOKER_ALWAYS_COMPRESS', false),
    ]
];
