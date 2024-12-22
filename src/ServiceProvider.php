<?php
    namespace Genericmilk\Cooker;

    use Illuminate\Support\Facades\Blade;

    class ServiceProvider extends \Illuminate\Support\ServiceProvider {



        public function boot()
        {
            $this->setupConfig(); // Load config
            $this->setupBladeDirectives(); // Setup blade directives
            $this->routes(); // Load routes
             
            if ($this->app->runningInConsole()) {
                $this->commands([
                    Commands\Install::class,
                    Commands\Get::class,
                    Commands\BuildCache::class,
                ]);
            }
        }
        public function register()
        {           
            // Engine
            /*
            $this->app->singleton('Genericmilk\Cooker\Engine', function ($app) {
                return new Engine();
            });
            */

            // Default Cookers
            /*
            $this->app->make('Genericmilk\Cooker\Ovens\Js');
            $this->app->make('Genericmilk\Cooker\Ovens\Less');
            $this->app->make('Genericmilk\Cooker\Ovens\Scss');  
            */
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

        protected function routes()
        {
            if ($this->app->routesAreCached()) {
                return;
            }
            require __DIR__.'/../routes/web.php';
        }

        protected function setupBladeDirectives(){
            Blade::directive('cooker', function ($file) {

                // tidy up quotes from file
                $file = str_replace("'", "", $file);
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $url = '/__cooker/'.$file;

                $mimes = [
                    'js' => 'application/javascript',
                    'less' => 'text/css',
                    'scss' => 'text/css',
                    'css' => 'text/css'
                ];

                if(!array_key_exists($ext, $mimes)){
                    return '<invalid cooker file="'.$file.'">';
                }

                $mime = $mimes[$ext];
            
                if($mime=='text/css'){
                    return "<link cooker href=\"".$url."\" rel=\"stylesheet\">";

                }elseif($mime=='application/javascript'){
                    return "<script cooker src=\"".$url."\" type=\"module\"></script>";
                }

            });
        }


    }
