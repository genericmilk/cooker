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

        $mimes = [
            'js' => 'application/javascript',
            'less' => 'text/css',
            'scss' => 'text/css',
            'css' => 'text/css'
        ];

        $classes = [
            'js' => Js::class,
            'less' => Less::class,
            'scss' => Scss::class,
            'css' => Css::class
        ];

        if(!array_key_exists($type, $mimes)){
            return response('Invalid file type', 404);
        }

        $this->baseFolder = resource_path($type);


        if(!file_exists($this->baseFolder)){
            return response('Resource folder not found', 404);
        }

        $ovens = collect(config('cooker.ovens'));

        $oven = (object)$ovens->where('file', $file)->first();

        if(!$oven){
            return response('Oven not found', 404);
        }

        // set the oven mime type
        $oven->mime = $mimes[$type];

        // update the paths to be full paths
        $oven->components['parse'] = array_map(function($file){
            return $this->baseFolder.'/'.$file;
        }, $oven->components['parse']);

        // build a fresh hash array of all the files
        $hashes = $this->hashes($oven);

        // get the current existing hash array
        $existingHashes = $this->existingHashes($oven);

        if(json_encode($hashes) == json_encode($existingHashes) && file_exists(base_path('.cooker/cache/'.$file))){
            // outputting the cache
        }else{
            
            // rebuild the cache
            $render = new $classes[$type]($oven);
            
            // output the render and the hashes
            file_put_contents(base_path('.cooker/cache/'.$file), $render->render());
            file_put_contents(base_path('.cooker/cache/'.$file.'.json'), json_encode($hashes));

        }

        return response($render->render(), 200, [
            'Content-Type' => $oven->mime
        ]);

    }

    private function hashes($oven)
    {
        $hashes = [];

        foreach($oven->components['parse'] as $file){
            $hashes[$file] = md5_file($file);
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