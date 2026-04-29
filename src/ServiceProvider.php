<?php

namespace Genericmilk\Cooker;

use Genericmilk\Cooker\Commands\AddCommand;
use Genericmilk\Cooker\Commands\CookCommand;
use Genericmilk\Cooker\Commands\InstallCommand;
use Genericmilk\Cooker\Commands\RemoveCommand;
use Genericmilk\Cooker\Commands\UninstallCommand;
use Genericmilk\Cooker\Commands\WatchCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cooker.php', 'cooker');

        $this->app->singleton(Cooker::class, fn () => Cooker::fromLaravel());
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/cooker.php' => config_path('cooker.php'),
        ], 'cooker-config');

        $this->registerBladeDirective();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                UninstallCommand::class,
                CookCommand::class,
                WatchCommand::class,
                AddCommand::class,
                RemoveCommand::class,
            ]);
        }
    }

    protected function registerBladeDirective(): void
    {
        Blade::directive('cooker', function (string $expression) {
            return "<?php echo \Genericmilk\Cooker\Blade\Tag::render($expression); ?>";
        });
    }
}
