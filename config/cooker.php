<?php
/*
 * Cooker 3
 *
 * (c) Peter Day (genericmilk) <peterday.main@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
return [

    /*
        The namespace to use in the javascript. By default the namespace is "app" and
        subsequently app.boot(); will run on the cooked javascript on document ready
    */

    'namespace' => 'app',

    /*
        Choose whether or not to include a built at timestamp at the top of scripts
    */
    'build_stamps' => [
        'css' => true,
        'js' => true
    ],


    /*
        Specify packages to be added to the appropriate builds
        before any libraries local to the project are built.
        Frameworks will be stored in the application cache and updated monthly.
        For a full list check out Cooker on github
    */
    'frameworks' => [
        'vue',
        'tailwind'
    ],

    'cookers' => [
        [
            'cooker' => 'Genericmilk\Cookers\Less',
            'libraries' => [
                /* ... */
            ],
            'input' => [
                'app.less'
            ],
            'output' => 'app.css'
        ],
        [
            'cooker' => 'Genericmilk\Cookers\Js',
            'libraries' => [
                /* ... */
            ],
            'input' => [
                'app.js'
            ],
            'output' => 'app.js'
        ]
    ],

    /*
        If set to true, Cooker will only output errors to the console
    */
    'silent' => false

    
];
