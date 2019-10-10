<?php

namespace Genericmilk\Cooker;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class Cooker extends Controller
{
    public static function Page($Url){
        // throw new \Exception('No Element Configuration Set');
        return (object)[
            'hello' => 'moto',
            'url' => $Url,
            'config' => config('element.api')
        ];
    }
}