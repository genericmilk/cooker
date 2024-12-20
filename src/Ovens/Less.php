<?php

namespace Genericmilk\Cooker\Ovens;
use App\Http\Controllers\Controller;

use Less_Parser;

class Less extends Controller
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
        $p = new Less_Parser();   
        foreach($this->parse as $input){
            $p->parseFile($input);
        }

        $output = $p->getCss();

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