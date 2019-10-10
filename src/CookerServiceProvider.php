<?php
    namespace Genericmilk\Cooker;
    use Illuminate\Support\ServiceProvider;



    class CookerServiceProvider extends ServiceProvider {



        public function boot()
        {
            $this->setupConfig(); // Load config
            $this->loadRoutesFrom(__DIR__.'/routes/web.php'); // Import routes

            $this->commands([
                Build::class
            ]);


            if ($this->app->runningInConsole()) {
                $this->commands([
                    Build::class
                ]);
            }
        }
        public function register()
        {
            // Import controllers
            $this->app->make('Genericmilk\Cooker\Cooker');
            $this->app->make('Genericmilk\Cooker\Build');
            
        }

        protected function setupConfig(){

            $source = realpath($raw = __DIR__.'/../config/cooker.php') ?: $raw;
            if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
                $this->publishes([$source => config_path('cooker.php')]);
            } elseif ($this->app instanceof LumenApplication) {
                $this->app->configure('cooker');
            }
            $this->mergeConfigFrom($source, 'cooker');
        }


    }