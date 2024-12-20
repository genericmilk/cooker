<?php

namespace Genericmilk\Cooker\Ovens;
use App\Http\Controllers\Controller;


class Css extends Controller
{
	
    protected $preload;
    protected $parse;

    public function __construct($oven)
    {
        $components = (object)$oven->components;

        $this->preload = $components?->preload ?? [];
        $this->parse = $components?->parse ?? [];

    }

    public function render()
    {
        $output = ''; 
        foreach($this->parse as $input){
            $output .= file_get_contents($input).PHP_EOL;
        }

        if($this->startupClass){
            $output .= 'new '.$this->startupClass.'();';
        }

        $output = $this->compress($output);

        return $output;
    }

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