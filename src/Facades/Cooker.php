<?php

namespace Genericmilk\Cooker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Genericmilk\Cooker\Build\Builder builder()
 * @method static \Genericmilk\Cooker\Build\Watcher watcher()
 * @method static \Genericmilk\Cooker\Build\Manifest manifest()
 * @method static \Genericmilk\Cooker\Toolbox\Toolbox toolbox()
 * @method static \Genericmilk\Cooker\Packages\Installer installer()
 * @method static \Genericmilk\Cooker\Packages\CookerJson cookerJson()
 * @method static string|null asset(string $name)
 * @method static string basePath(string $append = '')
 * @method static mixed config(string|null $key = null, mixed $default = null)
 */
class Cooker extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Genericmilk\Cooker\Cooker::class;
    }
}
