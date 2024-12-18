<?php
    namespace Genericmilk\Cooker;

    use Illuminate\Support\Facades\Blade;

    class ServiceProvider extends \Illuminate\Support\ServiceProvider {



        public function boot()
        {
            $this->setupConfig(); // Load config
            $this->setupBladeDirectives(); // Setup blade directives
             
            if ($this->app->runningInConsole()) {
                $this->commands([
                    Commands\Cook::class,
                    Commands\Init::class,
                    Commands\Watch::class,
                    Commands\Install::class
                ]);
            }
        }
        public function register()
        {           
            // Engine
            $this->app->singleton('Genericmilk\Cooker\Engine', function ($app) {
                return new Engine();
            });

            // Default Cookers
            $this->app->make('Genericmilk\Cooker\Ovens\Js');
            $this->app->make('Genericmilk\Cooker\Ovens\Less');
            $this->app->make('Genericmilk\Cooker\Ovens\Scss');  
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

        protected function setupBladeDirectives(){
            Blade::directive('cooker', function ($file) {

                // tidy up quotes from file
                $file = str_replace("'", "", $file);
                
                $url = '/__cooker/'.$file;
            
                if($ext=='css'){
                    return "<link cooker href=\"".$url."\" rel=\"stylesheet\">";

                }elseif($ext=='js'){
                    return "<script cooker src=\"".$url."\" type=\"module\"></script>";
                }

            });
        }


    }
