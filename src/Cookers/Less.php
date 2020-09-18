<?php

namespace Genericmilk\Cooker\Cookers;
use App\Http\Controllers\Controller;

use Less_Parser;

class Less extends Controller
{
	public $format = 'css';
    
    public static function cook($job){
        $p = new Less_Parser();   
        foreach($job['input'] as $input){
            $p->parseFile(resource_path('less/'.$input));
        }
        return $p->getCss();
    }

}