<?php

namespace Genericmilk\Cooker\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use Exception;
use stdClass;
use Throwable;

// Cooker subsystems
use Genericmilk\Cooker\Preloads;

// Cooker engines
use Genericmilk\Cooker\Ovens\Js;
use Genericmilk\Cooker\Ovens\Less;
use Genericmilk\Cooker\Ovens\Scss;

use function Laravel\Prompts\spin;


class Get extends Command
{
	protected $signature = 'cooker:get {package?} {version?}';	
    protected $description = 'Installs a Javascript package into your project';

    protected $version;
    protected $npmPlatform;

    protected $didInstall = false;

	public function __construct(){

        // check that the cooker.json file exists

        parent::__construct();
    }
    public function handle(){
		// Check if we have run setup and launch it if we need to
		if(is_null(config('cooker.ovens'))){
            $this->call('cooker:init');
            return;     
		}



        $this->dev = $this->setupEnv();
        $this->version = json_decode(file_get_contents(__DIR__.'/../../composer.json'))->version;
        $this->info('👨‍🍳 Cooker '.$this->version.' ('.ucfirst($this->env).')'.PHP_EOL);


        $this->npmPlatform = 'https://cdn.jsdelivr.net/npm/';

        $packages = [];

        if($this->argument('package')){
            $packages[] = $this->argument('package');
        } else {
            // Get all packages from the cooker.json file
            if(!file_exists(base_path('.cooker/cooker.json'))){
                $this->error('The cooker.json file does not exist in '.base_path('.cooker').'. Please check and try again.');
                return;
            }
            $cookerJson = json_decode(file_get_contents(base_path('.cooker/cooker.json')));
            if(isset($cookerJson->packages)){
                foreach($cookerJson->packages as $package => $version){
                    $packages[] = $package;
                }
            }
        }

        if(count($packages)==0){
            $this->error('No packages were listed in cooker.json and no new packages were specified for install. Please check and try again.');
            return;
        }

        foreach($packages as $package){
            $this->installPackage($package);
        }

        //$this->call('cook');
    }

    private function installPackage($package,$version = 'latest'){
        
        $response = spin(
            message: 'Fetching '.e($package).'...',
            callback: function() use ($package,$version){
                
                $response = Http::get('https://registry.npmjs.org/'.$package);
                if($response->failed()){
                    return '🤷‍♂️ Package not found. Please check and try again.';
                }

                $response = $response->object();
                $responseArray = json_decode(json_encode($response), true);
        
                // Convert response to an array (helps with some key names having - in them)
                if(!$this->validatePackageJson()){
                    return 'Your package json file is invalid. Please check and try again.';

                }
                                
                // Get the latest version
                $latestVersion = $responseArray['dist-tags']['latest'];

                $targetVersion = $latestVersion; // temp
                
                $cookerJson = json_decode(file_get_contents(base_path('.cooker/cooker.json')));
                $cookerPath = base_path('.cooker/packages');
            
                
                if(isset($cookerJson->packages->$package) && is_dir($cookerPath.'/'.$package) && file_exists($cookerPath.'/'.$package.'/'.$cookerJson->packages->$package.'.js')){
                    if($cookerJson->packages->$package == $targetVersion){
                        return '🔴 '.$package.'@'.$targetVersion.' is already installed';
                    }
                }


                // Grab the script
                $script = Http::get($this->npmPlatform.$package.'@'.$targetVersion);
                if($script->failed()){
                    return '🔴 Failed to download package. Could not communicate with repository';
                }

                // If the .cooker/packages folder doesn't exist, create it
                if (!file_exists(base_path('.cooker/packages'))){
                    $this->makeDirectory(base_path('.cooker/packages'));
                }

                // Make the package directory
                $scriptDir = base_path('.cooker/packages/'.$package);


                if (!file_exists($scriptDir)) {
                    $this->makeDirectory($scriptDir);
                }

                // Try to compress the script               
                try{
                    $script = Js::compress($script->body());
                }catch(Throwable $e){
                    $script = $script->body();
                }

                
                // Write the script to the json
                if(!isset($cookerJson->packages->$package)){
                    $cookerJson->packages->$package = new stdClass;
                }
                $cookerJson->packages->$package = $targetVersion;

                file_put_contents(base_path('.cooker/cooker.json'), json_encode($cookerJson, JSON_PRETTY_PRINT));
                file_put_contents(base_path('.cooker/packages/'.$package.'/'.$targetVersion.'.js'), $script);
        
                $this->didInstall = true;
                return '🟢 Installed '.$package.'@'.$targetVersion.' to '.config('app.name');


            }
        );

        $this->info($response);



    }

	// Helpers
    private function setupEnv(){
		$dev = config('app.debug');		
		$this->env = $dev ? 'dev' : 'prod';
		return $dev;
	}
    private function makeDirectory($f) {
		try{
			mkdir($f);
            if(!$this->option('silent')){
                $this->line('📁 Created '.$f);
            }
		}catch(\Exception $e){
			$this->error('✋ Could not create '.$f);
		}
	}
    private function validatePackageJson(){
        try{
            $cookerJson = json_decode(file_get_contents(base_path('.cooker/cooker.json')));
            if(isset($cookerJson->packages)){
                return true;
            }else{
                return false;
            }
        }catch(Exception $e){
            return false;
        }
        
    }
}