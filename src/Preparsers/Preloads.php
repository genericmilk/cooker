<?php

namespace Genericmilk\Cooker\Preparsers;
use App\Http\Controllers\Controller;

use Storage;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;


class Preloads extends Controller
{
    /* 
    *   Take the preloads array and import the files into the oven
    *   This may involve downloading or opening local files
    *   We do not parse these apart from enforcing js valid syntax
    */

    public static function obtain($preloads,$oven,$isDev){
        $o = ''; // make an output buffer
        foreach($preloads as $preload){
            $o .= Preloads::lastLineFormat($oven,Preloads::validatePreload($preload,$isDev));
        }
        return $o;
    }

    public static function validatePreload($p,$isDev){
        
        // First check if this is a string or an array. If array we need to pick the right version
        if(is_array($p)){

            // Quickly validate the array here
            if(!isset($p['prod']) || !isset($p['dev'])){
                throw new Exception('Cooker: Preload array provided but both prod and dev key need to be present');
            }

            // Now pick the right version
            $p = $p[$isDev ? 'dev' : 'prod'];
        }

        if (strpos($p, '://') !== false) {
            // Remote url
            if (strpos($p, 'http') === false) {
                throw new Exception('Cooker: Remote url provided for preload but no protocol provided. Please provide at least http or https: '.$p);
            }
            
            $cache_name = 'cooker-'.md5($p);

            if (!Cache::has($cache_name)){
                try{
                    $data = Http::get($p)->body();
                }catch(Exception $e){
                    throw new Exception('Cooker: Could not download remote file: '.$p);
                }
                Cache::forever($cache_name, $data);
                $o = $data;   
            }else{
                $o = Cache::get($cache_name);    
            }

        }else{
            // Local file
	        $p = resource_path($ext.'/'.$p);
            if(!file_exists($p)){
                throw new Exception('Cooker: Local preload file could not be found: '.$p);
            }

            $cache_name = 'cooker-'.md5($p);
            if (!Cache::has($cache_name)){
                try{
                    $data = file_get_contents($p);
                }catch(\Exception $e){
                    throw new Exception('Cooker: Could not read local file contents. '.$p.' did not pass validation');
                }
    
                Cache::forever($cache_name, $data);
                $o = $data;   
            }else{
                $o = Cache::get($cache_name);    
            }
        }
        
        return $o.PHP_EOL;
    }
    public static function lastLineFormat($oven,$input){
        if($oven->format!='js'){
            return $input;
        }
        if(substr($input, -1)!=';'){
			$input = $input.';';
		}
		return $input;
    }
}
