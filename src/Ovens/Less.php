<?php

namespace Genericmilk\Cooker\Ovens;
use App\Http\Controllers\Controller;

use Less_Parser;

class Less extends Controller
{
	public $format = 'css';
    public $directory = 'less';
    
    public static function cook($job){
        $p = new Less_Parser();   
        foreach($job['input'] as $input){
            $p->parseFile(resource_path('less/'.$input));
        }
        return $p->getCss();
    }
    public static function compress($input){
        
    }
}