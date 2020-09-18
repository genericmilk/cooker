<?php

function cooker_resource($file){
    $hash = config('app.debug') ? time() : md5(file_get_contents(public_path('build/'.$file)));
    return '/build/'.$file.'?build=' . $hash;
}