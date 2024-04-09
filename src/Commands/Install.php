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


class Install extends Command
{
	protected $signature = 'cooker:install {package?} {version?} {--silent} {--skipsetup}';	
    protected $description = 'Installs a Javascript package into your project using NPM';

    protected $version;
    protected $npmPlatform;

    protected $didInstall = false;

	public function __construct(){
        parent::__construct();
    }
    public function handle(){
		// Check if we have run setup and launch it if we need to
		if(is_null(config('cooker.silent'))){
			if(!$this->option('skipsetup')){
				$this->call('cooker:init');
				return;
			}
		}


        if(!$this->option('silent')){
            $this->dev = $this->setupEnv();
            $this->version = json_decode(file_get_contents(__DIR__.'/../../composer.json'))->version;

            !config('cooker.silent') ? $this->info('👨‍🍳 Cooker '.$this->version.' ('.ucfirst($this->env).')'.PHP_EOL) : '';
        }


        
        if($this->option('skipsetup')){
            config(['cooker.packageManager.packageManager' => 'jsdelivr']);
        }else{
            if(is_null(config('cooker.packageManager.packageManager'))){
                $this->error('Please follow the upgrade guide to add the package manager to your config file.');
                return;
            }
        }



        if(!in_array(config('cooker.packageManager.packageManager'),['jsdelivr','unpkg'])){
            $this->error('An invalid package manager was specified. Please check and try again.');
            return;
        }


        if(config('cooker.packageManager.packageManager')=='jsdelivr'){
            $this->npmPlatform = 'https://cdn.jsdelivr.net/npm/';
        } else {
            $this->npmPlatform = 'https://unpkg.com/';
        }


        $packages = [];

        if($this->argument('package')){
            $packages[] = $this->argument('package');
        } else {
            // Get all packages from the cooker.json file
            if(!file_exists(config('cooker.packageManager.packagesList'))){
                $this->error('The cooker.json file does not exist. Please check and try again.');
                return;
            }
            $cookerJson = json_decode(file_get_contents(config('cooker.packageManager.packagesList')));
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

        if($this->didInstall && !$this->option('silent')){
            $this->call('cook');
        }else{
            if(!$this->option('silent')){
                $this->line(PHP_EOL."✨ Share the love: https://github.com/genericmilk/cooker");
            }
        }
    }

    private function installPackage($package,$version = 'latest'){
        if(!$this->option('silent')){
            $this->line('👀 Searching repository for '.e($package).'...');
        }
        $response = Http::get('https://registry.npmjs.org/'.$package);
        if($response->failed()){
            $this->error('🤷‍♂️ Package not found. Please check and try again.');
            return;
        }
        $response = $response->object();

        $responseArray = json_decode(json_encode($response), true);

        // Convert response to an array (helps with some key names having - in them)
        if(!$this->validatePackageJson()){
            $this->error('Your package json file is invalid. Please check and try again.');
            return;
        }
        
        // Get the latest version
        $latestVersion = $responseArray['dist-tags']['latest'];

        $targetVersion = $latestVersion; // temp
        if(!$this->option('silent')){
            $this->line('✨ Found '.$response->name.'@'.$targetVersion.' - '.$response->description);
        }
        // Is this installed in the cooker.json file?
        if($this->option('skipsetup')){
            $cookerJson = base_path('cooker.json');
            $cookerPath = base_path('cooker_packages');
        }else{
            $cookerJson = json_decode(file_get_contents(config('cooker.packageManager.packagesList')));
            $cookerPath = config('cooker.packageManager.packagesPath');
        }
        
        if(isset($cookerJson->packages->$package) && is_dir($cookerPath.'/'.$package) && file_exists($cookerPath.'/'.$package.'/'.$cookerJson->packages->$package.'.js')){
            if($cookerJson->packages->$package == $targetVersion){
                if(!$this->option('silent')){
                    $this->line('🟠 This package is already installed.');
                }
                return;
            }
        }

        // Now grab the script
        if(!$this->option('silent')){
            $this->line('🛬 Installing '.$response->name.'@'.$targetVersion.' on '.config('app.name').'...');
        }
        

        // Grab the script using unpkg
        $script = Http::get($this->npmPlatform.$package.'@'.$targetVersion);
        if($script->failed()){
            $this->error('🔴 Failed to download package. Could not communicate with repository');
            return;
        }

        // If the cooker_resources folder doesn't exist, create it
        if(!$this->option('skipsetup')){
            if (!file_exists(config('cooker.packageManager.packagesPath'))) {
                $this->makeDirectory(config('cooker.packageManager.packagesPath'));
            }
        }

        // Make the package directory
        if(!$this->option('skipsetup')){
            $scriptDir = config('cooker.packageManager.packagesPath').'/'.$package;
        }else{
            $scriptDir = base_path('cooker_packages').'/'.$package;
        }
        if (!file_exists($scriptDir)) {
            $this->makeDirectory($scriptDir);
        }

        // Download the script to the package directory
        if(!$this->option('silent')){
            $this->line('👩‍🔧 Parsing script...');
        }
        try{
            $script = Js::compress($script->body());
        }catch(Throwable $e){
            $script = $script->body();
        }

        
        if(!$this->option('silent')){
            $this->line('📄 Writing to cooker.json...');
        }

        // Write the script to the json
        if(!isset($cookerJson->packages->$package)){
            $cookerJson->packages->$package = new stdClass;
        }
        $cookerJson->packages->$package = $targetVersion;
        if($this->option('skipsetup')){
            file_put_contents(base_path('cooker.json'), json_encode($cookerJson, JSON_PRETTY_PRINT));
        }else{
            file_put_contents(config('cooker.packageManager.packagesList'), json_encode($cookerJson, JSON_PRETTY_PRINT));
        }

        if(!$this->option('silent')){
            $this->line('📦 Wrapping up...');
        }

        if($this->option('skipsetup')){
            file_put_contents(config('cooker.packageManager.packagesPath').'/'.$package.'/'.$targetVersion.'.js', $script);
        }else{
            file_put_contents(base_path('cooker_packages').'/'.$package.'/'.$targetVersion.'.js', $script);
        }
    
        $this->didInstall = true;

        if(!$this->option('silent')){
            $this->line('🟢 Installed '.$package.'@'.$targetVersion.' to '.config('app.name'));
        }
        

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
            if($this->option('skipsetup')){
                $cookerJson = base_path('cooker.json');
            }else{
                $cookerJson = json_decode(file_get_contents(config('cooker.packageManager.packagesList')));                
            }
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