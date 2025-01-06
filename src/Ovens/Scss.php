<?php

namespace Genericmilk\Cooker\Ovens;
use App\Http\Controllers\Controller;

use ScssPhp\ScssPhp\Compiler;


class Scss extends Controller
{
    protected $preload;
    protected $parse;

    public function __construct($oven)
    {
        $components = (object)$oven->components;

        $this->preload = $components?->preload ?? [];
        $this->parse = $components?->parse ?? [];

    }

    public function render(): string
    {
        $scss = new Compiler();
        $output = '';
        foreach($this->parse as $input){
            $output .= $scss->compile(file_get_contents(resource_path('scss/'.$input))).PHP_EOL;

        }

        $output = $this->compress($output);

        return $output;
    }

    private function compress($input): string
    {
        $input = preg_replace('/\/\*((?!\*\/).)*\*\//','',$input); // negative look ahead
        $input = preg_replace('/\s{2,}/',' ',$input);
        $input = preg_replace('/\s*([:;{}])\s*/','$1',$input);
        $input = preg_replace('/;}/','}',$input);		
        return $input;
    }

}