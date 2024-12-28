<?php

namespace Genericmilk\Cooker;
use App\Http\Controllers\Controller;

use Illuminate\Http\Response;

use Genericmilk\Cooker\Ovens\Js;
use Genericmilk\Cooker\Ovens\Less;
use Genericmilk\Cooker\Ovens\Scss;
use Genericmilk\Cooker\Ovens\Css;


class Engine extends Controller
{

    protected $baseFolder;
    public $output = true;
    
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
            $render = file_get_contents(base_path('.cooker/cache/'.$file));
        }else{
            
            // setup the renderer
            $renderer = new $classes[$type]($oven);
            
            $render = $renderer->render();

            // output the render and the hashes
            file_put_contents(base_path('.cooker/cache/'.$file), $render);
            file_put_contents(base_path('.cooker/cache/'.$file.'.json'), json_encode($hashes));
            
            $render = (string)$render;
        }

        if($this->output){
            return response($render, 200, [
                'Content-Type' => $oven->mime
            ]);
        }

    }

    public function import($file): Response
    {
        $fileLoc = base_path('.cooker/imports/'.$file.'.js');
        if(!file_exists($fileLoc)){
            return response('Import not found', 404);
        }
        return response(file_get_contents($fileLoc), 200, [
            'Content-Type' => 'application/javascript'
        ]);
    }

    private function hashes($oven): array
    {
        $hashes = [];

        foreach($oven->components['parse'] as $file){
            $hashes[$file] = md5_file($file);
        }

        return $hashes;
    }

    private function existingHashes($oven): array
    {
        $hashFile = base_path('.cooker/cache/'.$oven->file.'.json');
        if(file_exists($hashFile)){
            return json_decode(file_get_contents($hashFile), true);
        }else{
            return [];
        }
    }

}