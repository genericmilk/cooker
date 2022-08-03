<?php
/*
 * Cooker 5
 *
 * (c) Peter Day (genericmilk) <peterday.main@gmail.com> 2022-2023
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
    'notifications' => true,

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
    'canSpeedyCook' => true,

    
    'ovens' => [
        [
            'cooker' => 'Genericmilk\Cooker\Ovens\Less',
            'preload' => [
                /*
                    Place any CDNs or local paths in the array here, These will be injected and not parsed before the appcode
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
                    Place any CDNs or local paths in the array here, These will be injected and not parsed before the appcode
                */
                'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js'
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
