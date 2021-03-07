<?php

namespace Genericmilk\Cooker\Ovens;
use App\Http\Controllers\Controller;

use ScssPhp\ScssPhp\Compiler;


class Scss extends Controller
{
    public $format = 'css';
    public $directory = 'scss';
    
    public static function cook($job){
        $scss = new Compiler();
        $o = '';
        foreach($job['input'] as $input){
            $o .= $scss->compile(file_get_contents(resource_path('scss/'.$input)));
        }
        return $o;
    }
}