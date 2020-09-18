<?php

namespace Genericmilk\Cooker\Cookers;

use App\Http\Controllers\Controller;

class Js extends Controller
{
    private $format = 'js';
    
    public static function cook($job){
        $o = $this->obtainFrameworks('js');
        $o .= $this->js_libr();
        foreach($job['libraries'] as $loclib){
            $o .= file_get_contents(resource_path('js/'.$loclib));
        }
        foreach($job['input'] as $input){
            if(file_exists(resource_path('js/'.$input))){
                $j = file_get_contents(resource_path('js/'.$input));
                if(!$this->dev){
                    $j = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '', $j); // remove js comments
                    $j = minify_js($j); // minify
                    $j = $this->lastLineFormat($j);
                }
                $o .= $j;
            }else{
                $this->error(resource_path('js/'.$input).' missing. Unable to mix in this cook session');
            }
        }

        $o .= is_null(config('cooker.namespace')) ? 'app' : config('cooker.namespace');
        $o .= '.boot();';

        $comment = config('cooker.build_stamps.js') ? "/* ".$job['output']." Generated by Cooker v".$this->version." by Genericmilk - Last build at ".Carbon::now()." */" : "";
        $o = $comment . $o;
        file_put_contents(public_path('build/'.$job['output']),$o); // write o
        !config('cooker.silent') ? $this->bar->advance() : '';
    }
    
}