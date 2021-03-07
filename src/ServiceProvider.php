<?php
    namespace Genericmilk\Cooker;

    class ServiceProvider extends \Illuminate\Support\ServiceProvider {



        public function boot()
        {
            $this->setupConfig(); // Load config
            if ($this->app->runningInConsole()) {
                $this->commands([
                    Commands\Build::class,
                    Commands\Setup::class
                ]);
            }
        }
        public function register()
        {
            // Commands
            $this->app->make('Genericmilk\Cooker\Commands\Build');
            $this->app->make('Genericmilk\Cooker\Commands\Setup');

            // Cookers
            $this->app->make('Genericmilk\Cooker\Cookers\Js');
            $this->app->make('Genericmilk\Cooker\Cookers\Less');
            $this->app->make('Genericmilk\Cooker\Cookers\Scss');  
        }

        protected function setupConfig(){

            $configPath = __DIR__ . '/../config/cooker.php';
            $this->publishes([$configPath => $this->getConfigPath()], 'config');
    
        }

        protected function getConfigPath()
        {
            return config_path('cooker.php');
        }

        protected function publishConfig($configPath)
        {
            $this->publishes([$configPath => config_path('cooker.php')], 'config');
        }


    }