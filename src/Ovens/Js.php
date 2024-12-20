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
        $this->startupClass = $components?->startupClass;

    }

    public function render(): string
    {
        $output = ''; 
        foreach($this->parse as $input){
            $output .= file_get_contents($input).PHP_EOL;
        }

        $output = $this->processImports($output);
        
        if($this->startupClass){
            $output .= 'new '.$this->startupClass.'();';
        }

        $output = $this->compress($output);

        return $output;
    }

    private function processImports($output): string
    {
        $pattern = '/import([ \n\t]*(?:[^ \n\t\{\}]+[ \n\t]*,?)?(?:[ \n\t]*\{(?:[ \n\t]*[^ \n\t"\'\{\}]+[ \n\t]*,?)+\})?[ \n\t]*)from[ \n\t]*([\'"])([^\'"\n]+)(?:[\'"])/';

        $output = preg_replace_callback($pattern, function($matches){
            $path = $matches[3];
            $path = str_replace('.js', '', $path);
            $path = str_replace('./', '', $path);
            $path = str_replace('../', '', $path);
            $path = str_replace('/', '.', $path);
            $path = str_replace('\\', '.', $path);
            $path = str_replace('@.', '', $path);

            $fileLoc = base_path('.cooker/imports').'/'.$path.'.js';

            return file_get_contents($fileLoc);
        }, $output);

        return $output;
    }

    private function compress($output): string
    {
        $min = Minifier::minify($output,['flaggedComments' => false]);
        $min = trim(preg_replace('/\s+/', ' ', $min));
        return $min;
    }
}