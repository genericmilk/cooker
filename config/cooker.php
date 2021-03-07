<?php
/*
 * Cooker 4
 *
 * (c) Peter Day (genericmilk) <peterday.main@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
return [

    'silent' => false,
    
    'ovens' => [
        [
            'cooker' => 'Genericmilk\Cooker\Ovens\Less',
            'preload' => [
                'https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css'
            ],
            'input' => [
                'app.less'
            ],
            'output' => 'app.css',
            'stamped' => true
        ],
        [
            'cooker' => 'Genericmilk\Cooker\Ovens\Js',
            'preload' => [
                'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js'
            ],
            'input' => [
                'app.js'
            ],
            'namespace' => 'app',
            'output' => 'app.js',
            'stamped' => true
        ]
    ]    
];