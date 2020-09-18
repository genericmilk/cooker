<?php

namespace Genericmilk\Cooker\Cookers;
use App\Http\Controllers\Controller;

use ScssPhp\ScssPhp\Compiler;


class Scss extends Controller
{
    private $format = 'css';
    
    public static function cook($job){
        $scss = new Compiler();
        $o = '';
        foreach($job['input'] as $input){
            $o .= $scss->compile(file_get_contents(resource_path('scss/'.$input)));
        }
        return $o;
    }
}