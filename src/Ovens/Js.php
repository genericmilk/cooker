<?php

namespace Genericmilk\Cooker\Ovens;

use App\Http\Controllers\Controller;

use JShrink\Minifier;

class Js extends Controller
{    

    protected $preload;
    protected $parse;
    protected $startupClass;

    public function __construct($oven)
    {
        $components = (object)$oven->components;

        $this->preload = $components?->preload ?? [];
        $this->parse = $components?->parse ?? [];
        $this->startupClass = isset($components?->startupClass) ? $components?->startupClass : null;
    }

    public function render(): string
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

    private function compress($input): string
    {
        $min = Minifier::minify($input,['flaggedComments' => false]);
        $min = trim(preg_replace('/\s+/', ' ', $min));
        return $min;
    }
}