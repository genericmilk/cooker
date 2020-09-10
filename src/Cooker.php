<?php

namespace Genericmilk\Cooker;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class Cooker extends Controller
{
    public static function helper($file){
        $hash = env('APP_DEBUG') ? time() : md5(file_get_contents(public_path('build/'.$file)));
        return '/build/'.$file.'?build=' . $hash;
    }
}