<?php

namespace Genericmilk\Cooker\Preparsers;
use App\Http\Controllers\Controller;

use Storage;
use Exception;
use Illuminate\Support\Facades\Cache;


class Packages extends Controller
{
    /*
    *   Get all the files in the "libraries" dir for the job and return them as a string
    *   We do not parse these apart from enforcing js valid syntax
    */
    public static function obtain(){
        try{
            $cookerJson = json_decode(file_get_contents(config('cooker.packageManager.packagesList')));
            $packages = $cookerJson->packages;
            $o = '';
            foreach($packages as $pkgName => $pkgVersion){
                $o .= file_get_contents(config('cooker.packageManager.packagesDir').'/'.$pkgName.'/'.$pkgVersion.'.js');
            }
            return $o;
        }catch(Exception $e){
            throw new Exception('Cooker: Could not load packages. Please run php artisan cooker:install to install missing packages');
        }

    }
}
