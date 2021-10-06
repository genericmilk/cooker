<?php

namespace Genericmilk\Cooker;
use App\Http\Controllers\Controller;

use Genericmilk\Telephone\Telephone;
use Storage;
use Exception;
use Illuminate\Support\Facades\Cache;


class Preloads extends Controller
{
    public static function obtain($preloads,$oven){
        $o = '';
        foreach($preloads as $preload){
            $o .= Preloads::lastLineFormat(Preloads::validatePreload($preload,$oven->format));
        }
        return $o;
    }
    public static function validatePreload($p,$t){
        $ext = pathinfo($p, PATHINFO_EXTENSION);
        if($ext!=$t){
            throw new Exception('Cooker: Mismatching type of file for oven format on preload. '.$p.' did not pass validation');
        }
        
        if (strpos($p, '://') !== false) {
            // Remote url
            if (strpos($p, 'http') === false) {
                throw new Exception('Cooker: Remote url provided for preload but no protocol provided. Please provide at least http or https. '.$p.' did not pass validation');
            }
            
            $cache_name = 'cooker-'.md5($p);

            if (!Cache::has($cache_name)){
                try{
                    $data = file_get_contents($p);
                }catch(Exception $e){
                    throw new Exception('Cooker: Could not download remote file. '.$p.' did not pass validation');
                }
                Cache::forever($cache_name, $data);
                $o = $data;   
            }else{
                $o = Cache::get($cache_name);    
            }

        }else{
	        $p = resource_path($ext.'/'.$p);
            if(!file_exists($p)){
                throw new Exception('Cooker: Local preload file could not be found. '.$p.' did not pass validation');
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
    public static function lastLineFormat($input){
        if(substr($input, -1)!=';'){
			$input = $input.';';
		}
		return $input;
    }
}
