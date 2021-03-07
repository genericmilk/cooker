<?php

namespace Genericmilk\Cooker;
use App\Http\Controllers\Controller;

use Genericmilk\Telephone\Telephone;
use Storage;
use Exception;
use Cache;

class Preloads extends Controller
{
    public static function obtain($preloads,$oven){
        foreach($preloads as $preload){
            Preloads::validatePreload($preload,$oven->format);

        }
        $o = '';
        dd($preloads,$oven);
        foreach($frameworks as $f){
            if ( !file_exists( storage_path('app/cooker_frameworks_cache/'.$f) ) && !is_dir( storage_path('app/cooker_frameworks_cache/'.$f) ) ) {
                // Need to download
                $package = Telephone::call('http://registry.npmjs.org/'.$f)->get();
                $tar = (end($package->versions)->dist->tarball);
                Storage::put('cooker_frameworks_cache/'.$f.'.tgz', file_get_contents($tar));            

                $p = new \PharData(storage_path('app/cooker_frameworks_cache/'.$f.'.tgz'));
                $p->extractTo(storage_path('app/cooker_frameworks_cache/'.$f.'_download'));
                unlink(storage_path('app/cooker_frameworks_cache/'.$f.'.tgz'));
                rename(storage_path('app/cooker_frameworks_cache/'.$f.'_download/package/dist'),storage_path('app/cooker_frameworks_cache/'.$f));
                $this->delete_files(storage_path('app/cooker_frameworks_cache/'.$f.'_download'));
            }
 
            
            $dir = scandir(storage_path('app/cooker_frameworks_cache/'.$f));
			unset($dir[0]);
			unset($dir[1]);
			if (($key = array_search('.DS_Store', $dir)) !== false) {
				unset($dir[$key]);
			}
            $dir = array_values($dir);
			foreach($dir as $framework){                
                $ext = !$dev ? '.min.'.$type : '.'.$type;                
                if((pathinfo($framework, PATHINFO_EXTENSION))==$type){
                    if (strpos($framework, '.min.') !== false) {
                        if(!$dev){
                            $o .= file_get_contents(storage_path('app/cooker_frameworks_cache/'.$f.'/'.$framework));
                        }
                    }else{
                        if($dev){
                            $o .= file_get_contents(storage_path('app/cooker_frameworks_cache/'.$f.'/'.$framework));
                        }
                    }
                }
                
            }
        }
        return $o;
    }
    public static function validatePreload($p,$t){
        if (strpos($p, '://') !== false) {
            // Remote url
            if (strpos($p, 'http') === false) {
                throw new Exception('Cooker: Remote url provided for preload but no protocol provided. Please provide at least http or https. '.$p.' did not pass validation');
            }
            $ext = pathinfo($p, PATHINFO_EXTENSION);
            if($ext!=$t){
                throw new Exception('Cooker: Mismatching type of file for oven format on preload. '.$p.' did not pass validation');
            }
        }
    }
    private function delete_files($target) {
        if(is_dir($target)){
            $files = glob( $target . '*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned
            foreach( $files as $file ){
                $this->delete_files( $file );      
            }
            try{
                rmdir( $target );
            }catch(\Exception $e){
            }
        } elseif(is_file($target)) {
            unlink( $target );  
        }
    }
}