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
    
    'jobs' => [
        [
            'cooker' => 'Genericmilk\Cooker\Cookers\Less',
            'frameworks' => [
                'tailwindcss'
            ],
            'libraries' => [
                /* ... */
            ],
            'input' => [
                'app.less'
            ],
            'output' => 'app.css',
            'stamped' => true
        ],
        [
            'cooker' => 'Genericmilk\Cooker\Cookers\Js',
            'frameworks' => [
                'vue'
            ],
            'libraries' => [
                /* ... */
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