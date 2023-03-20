<?php

namespace Genericmilk\Cooker\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use Exception;

// Cooker subsystems
use Genericmilk\Cooker\Preloads;

// Cooker engines
use Genericmilk\Cooker\Ovens\Js;
use Genericmilk\Cooker\Ovens\Less;
use Genericmilk\Cooker\Ovens\Scss;


class Install extends Command
{
	protected $signature = 'cooker:install {package?} {version?}';	
    protected $description = 'Installs a Javascript package into your project using NPM';

    protected $version;

	public function __construct(){
        parent::__construct();
    }
    public function handle(){
        $this->version = json_decode(file_get_contents(__DIR__.'/../../composer.json'))->version;
		$this->dev = $this->setupEnv();
		
		!config('cooker.silent') ? $this->info('👨‍🍳 Cooker '.$this->version.' ('.ucfirst($this->env).')'.PHP_EOL) : '';
        
        $packages = [];

        if($this->argument('package')){
            $packages[] = $this->argument('package');
        } else {
            // Get all packages from the cooker.json file
        }


        foreach($packages as $package){
            $this->installPackage($package);
        }

    }

    private function installPackage($package,$version = 'latest'){
        $this->line('Searching repository...');
        $response = Http::get('https://registry.npmjs.org/'.$package);
        if($response->failed()){
            $this->error('Package not found. Please check and try again.');
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

        $this->line('✨ Found '.$response->name.'@'.$targetVersion.' - '.$response->description);

        // Is this installed in the cooker.json file?
        $cookerJson = json_decode(file_get_contents(config('cooker.packageManager.packagesList')));
        

        // Now grab the script
        $this->line('Installing to '.$response->name.'@'.$targetVersion.' '.config('app.name').'...');

        // Grab the script using unpkg
        $script = Http::get('https://unpkg.com/'.$package.'@'.$targetVersion);
        if($script->failed()){
            $this->error('Failed to download package');
            return;
        }

        // If the cooker_resources folder doesn't exist, create it
        if (!file_exists(config('cooker.packageManager.packagesPath'))) {
            $this->makeDirectory(config('cooker.packageManager.packagesPath'));
        }


        // Make the package directory
        $scriptDir = config('cooker.packageManager.packagesPath').'/'.$package;
        if (!file_exists($scriptDir)) {
            $this->makeDirectory($scriptDir);
        }

        // Download the script to the package directory
        $this->line('👩‍🔧 Parsing script...');
        $script = Js::compress($script->body());
        
        
        $this->line('📦 Wrapping up...');
        file_put_contents(config('cooker.packageManager.packagesPath').'/'.$package.'/'.$targetVersion.'.js', $script);
        
        $this->info('✅ Installed '.$package.'@'.$targetVersion.' to '.config('app.name'));
        
    }

	// Helpers
	private function compress($input,$type){
		/*
			Squashes files, but only if we're in production
		*/

        $input = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '', $input); // remove js comments

        if(trim($input) === "") return $input;
        $input =  preg_replace(
            array(
                // Remove comment(s)
                '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
                // Remove white-space(s) outside the string and regex
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
                // Remove the last semicolon
                '#;+\}#',
                // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
                '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
                // --ibid. From `foo['bar']` to `foo.bar`
                '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i'
            ),
            array(
                '$1',
                '$1$2',
                '}',
                '$1$3',
                '$1.$3'
            ),
        $input);
		

		$input = $this->lastLineFormat($input,$type);
		return $input;
	}
    private function lastLineFormat($input,$type){
		/*
			Fixes file concatanation by ensuring last charachter is a ; so that differentiation
			between scripts is met
		*/
		if(substr($input, -1)!=';' && $type=='js'){
			$input = $input.';';
		}
		return $input;
	}
    private function setupEnv(){
		$dev = config('app.debug');		
		$this->env = $dev ? 'dev' : 'prod';
		return $dev;
	}
    private function makeDirectory($f) {
		try{
			mkdir($f);
			$this->line('📁 Created '.$f);
		}catch(\Exception $e){
			$this->error('✋ Could not create '.$f);
		}
	}
    private function validatePackageJson(){
        try{
            $cookerJson = json_decode(file_get_contents(config('cooker.packageManager.packagesList')));
            if(isset($cookerJson->packages)){
                return true;
            }else{
                return false;
            }
        }catch(Throwable $e){
            return false;
        }
        
    }
}