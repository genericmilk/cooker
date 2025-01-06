<?php

namespace Genericmilk\Cooker\Ovens;

use App\Http\Controllers\Controller;

use Peast\Peast;
use Peast\Renderer;
use Peast\Formatter\PrettyPrint;
use Peast\Formatter\Compact;


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
        $this->baseFile = $oven->file;

    }

    public function render(): string
    {
        $output = ''; 
        foreach($this->parse as $input){
            $output .= file_get_contents($input).PHP_EOL;
        }
        

        $ast = Peast::latest($output, [
            'sourceType' => Peast::SOURCE_TYPE_MODULE
        ])->parse();

        // find ImportDeclaration nodes
        $ast->traverse(function($node) {
            if ($node->getType() === 'ImportDeclaration') {
                
                // does the node value start with an @ symbol?
                if (substr($node->getSource()->getValue(), 0, 1) === '@') {
                    // we are importing from /resources/js/imports
                    $nodeValue = $node->getSource()->getValue();
                    $nodeValue = str_replace('@/', '', $nodeValue);
                    $node->getSource()->setValue('/__cooker/local-imports/'.$this->baseFile.'/' . $nodeValue);
                }else{
                    // we are importing from .cooker/imports
                    $node->getSource()->setValue('/__cooker/package-imports/'.$this->baseFile.'/' . $node->getSource()->getValue());
                }


            }
        });

        
        $renderer = new Renderer;
        if (config('app.debug')) {
            $renderer->setFormatter(new PrettyPrint);
        }else{
            $renderer->setFormatter(new Compact);
        }

        $output = $renderer->render($ast);

        return $output;
    }


}