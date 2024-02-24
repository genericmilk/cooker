<?php

namespace Genericmilk\Cooker\Preparsers;
use App\Http\Controllers\Controller;

use Storage;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;


class Frameworks extends Controller
{
    /* 
    *   Take the preloads array and import the files into the oven
    *   This may involve downloading or opening local files
    *   We do not parse these apart from enforcing js valid syntax
    */

    public static function obtain($isDev){

        $frameworksList = config('cooker.frameworks');


        $o = ''; // make an output buffer
        foreach($frameworksList as $framework){
            $o .= Frameworks::lastLineFormat(Frameworks::get($framework,$isDev));
        }
        return $o;
    }

    public static function get($framework,$isDev){

        if($framework=='vue'){
            $url = $isDev ? 'https://cdn.jsdelivr.net/npm/vue@3.4.19/dist/vue.global.min.js' : 'https://cdn.jsdelivr.net/npm/vue@3.4.19/dist/vue.global.prod.js';
        }else if($framework=='react'){
            $url = $isDev ? 'https://cdn.jsdelivr.net/npm/react@18.2.0/umd/react.development.js' : 'https://cdn.jsdelivr.net/npm/react@18.2.0/umd/react.production.min.js';
        }else if($framework=='angular'){
            $url = 'https://cdn.jsdelivr.net/npm/angular@1.8.3/angular';
        }else if($framework=='jquery'){
            $url = $isDev ? 'https://code.jquery.com/jquery-3.6.0.min.js' : 'https://code.jquery.com/jquery-3.6.0.js';
        }else if($framework=='tailwind'){
            $url = 'https://cdn.tailwindcss.com';
        }

        // Remote url
        
        $cache_name = 'cooker-'.md5($framework);

        if (!Cache::has($cache_name)){
            try{
                $data = Http::get($url)->body();
            }catch(Exception $e){
                throw new Exception('Cooker: Could not download remote file: '.$uel);
            }
            Cache::forever($cache_name, $data);
            $o = $data;
        }else{
            $o = Cache::get($cache_name);    
        }

        
        return $o.PHP_EOL;
    }
    public static function lastLineFormat($input){
        if(substr($input, -1)!=';'){
			$input = $input.';';
		}
		return $input;
    }
}
