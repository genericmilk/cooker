<?php

namespace Genericmilk\Cooker\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Genericmilk\Cooker\ServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }
}