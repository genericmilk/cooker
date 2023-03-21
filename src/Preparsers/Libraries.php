<?php

namespace Genericmilk\Cooker\Preparsers;
use App\Http\Controllers\Controller;

use Storage;
use Exception;
use Illuminate\Support\Facades\Cache;


class Libraries extends Controller
{
    /*
    *   Get all the files in the "libraries" dir for the job and return them as a string
    *   We do not parse these apart from enforcing js valid syntax
    */
    public static function obtain($oven){
        // Global less libs (All common everywhere)
        try{
            $dir = scandir(resource_path($oven->directory.'/libraries'));
            unset($dir[0]);
            unset($dir[1]);
            if (($key = array_search('.DS_Store', $dir)) !== false) {
                unset($dir[$key]);
            }
            $dir = array_values($dir);
            $libs = '';
            foreach($dir as $lib){
                $libs .= $this->lastLineFormat(file_get_contents(resource_path($oven->directory.'/libraries/'.$lib)),$oven->format);
            }
            return $libs;
        }catch(Exception $e){
            return null;
        }
    }
    private function lastLineFormat($input,$type){
        if($type=='js'){
            if(substr($input, -1)!=';'){
                $input = $input.';';
            }
        }
        return $input;        
    }
}
