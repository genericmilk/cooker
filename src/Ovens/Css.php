<?php

namespace Genericmilk\Cooker\Ovens;
use App\Http\Controllers\Controller;


class Css extends Controller
{
	public $format = 'css';
    public $directory = 'css';
    
    public static function cook($job){
        $p = ''; 
        foreach($job['input'] as $input){
            $p .= file_get_contents(resource_path('css/'.$input));
        }
        return $p;
    }
    public static function compress($input){
        $input = preg_replace('/\/\*((?!\*\/).)*\*\//','',$input); // negative look ahead
        $input = preg_replace('/\s{2,}/',' ',$input);
        $input = preg_replace('/\s*([:;{}])\s*/','$1',$input);
        $input = preg_replace('/;}/','}',$input);		
        return $input;
    }
}