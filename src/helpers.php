<?php

function cooker_resource($file){
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
        return '<link href="'.$url.'" rel="stylesheet">';
    }elseif($ext=='js'){
        return '<script src="'.$url.'"></script>';
    }

}
