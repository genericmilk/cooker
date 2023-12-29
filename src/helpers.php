<?php

function cooker_resource($file,$isModule = false){
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $missingEmbed = '<meta name="missing" content="'.$file.'">';

    if (!file_exists(public_path('build'))){
        return null;
    }
    if (!file_exists(public_path('build/'.$file))){
         return null;
    }
    
    $hash = config('app.debug') ? time() : md5(file_get_contents(public_path('build/'.$file)));
    $url = '/build/'.$file.'?build=' . $hash;

    $ext = pathinfo($file, PATHINFO_EXTENSION);

    if($ext=='css'){
        $fileOutput = '<link href="'.$url.'" rel="stylesheet">';
    }elseif($ext=='js'){
        $fileOutput = '<script src="'.$url.'" type="'.($isModule ? 'module' : 'text/javascript' ).'"></script>';
    }

    return $missingEmbed.PHP_EOL.$fileOutput;
}