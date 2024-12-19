<?php

namespace Genericmilk\Cooker;
use App\Http\Controllers\Controller;

use Genericmilk\Cooker\Ovens\Js;
use Genericmilk\Cooker\Ovens\Less;
use Genericmilk\Cooker\Ovens\Scss;
use Genericmilk\Cooker\Ovens\Css;


class Engine extends Controller
{

    protected $baseFolder;
    
    public function render($file)
    {
        $type = pathinfo($file, PATHINFO_EXTENSION);

        $this->baseFolder = base_path('resources/'.$type);


        if(!file_exists($this->baseFolder)){
            return response('Resource folder not found', 404);
        }

        $ovens = collect(config('cooker.ovens'));

        $oven = (object)$ovens->where('file', $file)->first();

        if(!$oven){
            return response('Oven not found', 404);
        }

        // build a fresh hash array of all the files
        $hashes = $this->hashes($oven);

        // get the current existing hash array
        $existingHashes = $this->existingHashes($oven);

        if(json_encode($hashes) == json_encode($existingHashes)){
            // outputting the cache
            dd('cache');
        }else{
            // rebuild the cache
            dd('rebuild');
        }

        dd($hashes);
    }

    private function hashes($oven)
    {
        $hashes = [];

        foreach($oven->components['parse'] as $file){
            $fileToHash = $this->baseFolder.'/'.$file;
            $hashes[$file] = md5_file($fileToHash);
        }

        return $hashes;
    }

    private function existingHashes($oven)
    {
        $hashFile = base_path('.cooker/cache/'.$oven->file.'.json');
        if(file_exists($hashFile)){
            return json_decode(file_get_contents($hashFile), true);
        }else{
            return [];
        }
    }

}