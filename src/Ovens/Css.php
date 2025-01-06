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

    private function compress($input){
        $input = preg_replace('/\/\*((?!\*\/).)*\*\//','',$input); // negative look ahead
        $input = preg_replace('/\s{2,}/',' ',$input);
        $input = preg_replace('/\s*([:;{}])\s*/','$1',$input);
        $input = preg_replace('/;}/','}',$input);		
        return $input;
    }
}