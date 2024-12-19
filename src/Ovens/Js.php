<?php

namespace Genericmilk\Cooker\Ovens;

use App\Http\Controllers\Controller;

use JShrink\Minifier;

class Js extends Controller
{    
    public function __construct($oven)
    {
        $components = (object)$oven->components;

        $preload = $components?->preload ?? [];
        $parse = $components?->parse ?? [];
        $startupClass = $components?->startupClass;

        dd($oven);

        $p = ''; 
        foreach($parse as $input){
            $p .= Js::lastLineFormat(file_get_contents(resource_path('js/'.$input)));
        }
        return $p;
    }    
    public static function lastLineFormat($input){
        if(substr($input, -1)!=';'){
			$input = $input.';';
		}
		return $input;
    }
    public static function compress($input){
        $min = Minifier::minify($input,['flaggedComments' => false]);
        $min = trim(preg_replace('/\s+/', ' ', $min));
        $min = Js::lastLineFormat($min);
        return $min;
    }
}