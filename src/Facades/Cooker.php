<?php

declare(strict_types=1);
namespace Genericmilk\Cooker\Facades;
use Illuminate\Support\Facades\Facade;

class Cooker extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Cooker';
    }
}
