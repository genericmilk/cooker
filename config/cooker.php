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

    'silent' => false,
    'notifications' => true,
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
