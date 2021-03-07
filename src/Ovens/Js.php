<?php

namespace Genericmilk\Cooker\Ovens;

use App\Http\Controllers\Controller;

class Js extends Controller
{
    public $format = 'js';
    public $directory = 'js';
    
    public static function cook($job){
        $p = '';   
        foreach($job['input'] as $input){
            $p .= file_get_contents(resource_path('js/'.$input));
        }
        return $p;
    }    
}