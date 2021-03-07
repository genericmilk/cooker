<?php

namespace Genericmilk\Cooker;
use App\Http\Controllers\Controller;

use Genericmilk\Telephone\Telephone;
use Storage;


class Preloads extends Controller
{
    public static function obtain($frameworks,$type,$dev){
        $o = '';
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
    private  function delete_files($target) {
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