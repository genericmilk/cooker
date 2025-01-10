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
use function Laravel\Prompts\note;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\error;
use function Laravel\Prompts\alert;
use function Laravel\Prompts\confirm;


class Get extends Command
{
	protected $signature = 'cooker:get {package?} {--remove} {--force}';	
    protected $description = 'Installs a Javascript package from NPM into your Cooker project';

    protected $version;
    protected $npmPlatform;

    protected $didInstall = false;

    public function __construct()
    {
        parent::__construct();
        $this->version = json_decode(file_get_contents(__DIR__.'/../../composer.json'))->version;
        $this->npmPlatform = 'https://unpkg.com/';
    }

    public function handle(): void
    {
		// Check if we have run setup and launch it if we need to
		if(is_null(config('cooker.ovens'))){
            error('Cooker is not installed. If you want to install, please run php artisan cooker:install');
            return;
		}

		note('👨‍🍳 Cooker '.$this->version);

        $installing = !$this->option('remove');
        $handlingAll = !$this->argument('package');
        $packagesToInstall = [];

        // make a new cooker.json file if it doesn't exist
        if(!file_exists(base_path('.cooker/cooker.json'))){
            $cookerJson = new stdClass;
            $cookerJson->packages = new stdClass;
            file_put_contents(base_path('.cooker/cooker.json'), json_encode($cookerJson, JSON_PRETTY_PRINT));
        }

        $cookerPackages = json_decode(file_get_contents(base_path('.cooker/cooker.json')))->packages;

        if($installing){
            if($handlingAll){
                // We are installing all packages
                if(count($cookerPackages)==0){
                    error('No packages were listed in cooker.json so there is no packages for us to install. Please check and try again.');
                    return;
                }
                $packages = $cookerPackages;

            }else{
                // We are installing a specific package
                $packages[] = $this->argument('package');
            }
        }else{
            if($handlingAll){
                error('You must specify a package to remove. Please check and try again.');
                return;
            }
        }

        $label = count($packages)==1 ? ('Installing 1 package') : ('Installing '.count($packages).' packages');

        if(count($packages)>0){
            $label .= ' - ('.implode(', ',$packages).')';
        }


        // confirm the user wants to install
        if(!$this->option('force')){
            $confirmed = confirm(
                label: 'Are you sure you want to '.($installing ? 'install' : 'remove').' these packages?',
                default: false,
                yes: ($installing ? 'Install' : 'Remove'),
                no: 'Cancel',
                hint: $label
            );

            if(!$confirmed){
                return;
            }
        }


        foreach($packages as $package){
            $this->installPackage($package);
        }

        $this->call('cooker:cook');
    }

    private function installPackage($package): void
    {
        $response = spin(
            message: 'Fetching '.e($package).'...',
            callback: function() use ($package){
                
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
                if(!isset($responseArray['dist-tags']['latest'])){
                    return '🔴 Could not find latest version of package';
                }

                $latestVersion = $responseArray['dist-tags']['latest'];

                $targetVersion = $latestVersion; // temp
                
                $cookerJson = json_decode(file_get_contents(base_path('.cooker/cooker.json')));
                $cookerPath = base_path('.cooker/imports');
            
                
                if(isset($cookerJson->packages->$package) && is_dir($cookerPath.'/'.$package) && file_exists($cookerPath.'/'.$package.'/'.$cookerJson->packages->$package.'.js')){
                    if($cookerJson->packages->$package == $targetVersion){
                        return '🔴 '.$package.'@'.$targetVersion.' is already installed';                   
                    }
                }


                // Load the url and find the final redirect url
                $urlToCheck = $this->npmPlatform.$package.'@'.$targetVersion;

                // Check the meta
                $meta = Http::get($this->npmPlatform.$package.'@'.$targetVersion.'?meta');

                if($meta->failed()){
                    return '🔴 Failed to download package. Could not communicate with repository';
                }

                $meta = $meta->object();

                $finalUrl = $this->npmPlatform.$package.'@'.$targetVersion.$meta->path.'?module';
                
                
                // Grab the script
                $script = Http::get($finalUrl);
                if($script->failed()){
                    return '🔴 Failed to download package. Could not communicate with repository';
                }

                // If the .cooker/imports folder doesn't exist, create it
                if (!file_exists(base_path('.cooker/imports'))){
                    $this->makeDirectory(base_path('.cooker/imports'));
                }

                // Try to compress the script               
                $script = $script->body();

                
                // Write the script to the json
                if(!isset($cookerJson->packages->$package)){
                    $cookerJson->packages->$package = new stdClass;
                }
                $cookerJson->packages->$package = $targetVersion;

                file_put_contents(base_path('.cooker/cooker.json'), json_encode($cookerJson, JSON_PRETTY_PRINT));
                file_put_contents(base_path('.cooker/imports/'.$package.'.js'), $script);
        
                $this->didInstall = true;
            }
        );
    }

	// Helpers
    private function makeDirectory($f): void
    {
		try{
			mkdir($f);
            if(!$this->option('silent')){
                $this->line('📁 Created '.$f);
            }
		}catch(\Exception $e){
			$this->error('✋ Could not create '.$f);
		}
	}
    private function validatePackageJson(): bool
    {
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