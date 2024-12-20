<?php

namespace Genericmilk\Cooker\Ovens;

use App\Http\Controllers\Controller;

use JShrink\Minifier;
use Peast\Peast;
use Peast\Renderer;
use Peast\Formatter\PrettyPrint;

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
        $this->startupClass = $components?->startupClass;

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

        $ast = Peast::latest($output, [
            'sourceType' => Peast::SOURCE_TYPE_MODULE
        ])->parse();
        //dd($output);

        $renderer = new Renderer;
        $renderer->setFormatter(new PrettyPrint);
        $output = $renderer->render($ast);


        //$output = $this->compress($output);

        return $output;
    }


    private function compress($output): string
    {
        $min = Minifier::minify($output,['flaggedComments' => false]);
        $min = trim(preg_replace('/\s+/', ' ', $min));
        return $min;
    }
}