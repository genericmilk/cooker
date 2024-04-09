<?php
    namespace Genericmilk\Cooker;

    require_once __DIR__.'/helpers.php';

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
            Blade::directive('cooker', function ($file,$isModule = false) {

                // tidy up quotes from file
                $file = str_replace("'", "", $file);

                if (!file_exists(public_path('build'))){
                    return 'no build folder';
                }
                if (!file_exists(public_path('build/'.$file))){
                     return 'no file';
                }
                
                $hash = config('app.debug') ? time() : md5(file_get_contents(public_path('build/'.$file)));
                $url = '/build/'.$file.'?build=' . $hash;
            
                $ext = pathinfo($file, PATHINFO_EXTENSION);
            
                if($ext=='css'){

                    return '<?php echo "<link href=\"/build/'.$file.'?build="; 
                    echo config(\'app.debug\') ? time() : \'prod\'; 
                    echo "\" rel=\"stylesheet\">"; ?>';

                }elseif($ext=='js'){

                    if($isModule){
                        return '<?php echo "<script src=\"/build/'.$file.'?build="; 
                        echo config(\'app.debug\') ? time() : \'prod\'; 
                        echo "\" type=\"module\"></script>"; ?>';                            
                    }else{
                        return '<?php echo "<script src=\"/build/'.$file.'?build="; 
                        echo config(\'app.debug\') ? time() : \'prod\'; 
                        echo "\" type=\"text/javascript\"></script>"; ?>';
                    }

                }




            });
        }


    }
