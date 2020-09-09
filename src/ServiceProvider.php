<?php
    namespace Genericmilk\Cooker;

    class ServiceProvider extends \Illuminate\Support\ServiceProvider{



        public function boot()
        {
            $this->setupConfig(); // Load config

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
            $this->app->make('Genericmilk\Cooker\Build');
            
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