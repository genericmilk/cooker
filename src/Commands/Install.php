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
	protected $signature = 'cooker:install {package}';	
    protected $description = 'Installs a Javascript package into your project using NPM';

    protected $version;

	public function __construct(){
        parent::__construct();
    }
    public function handle(){
        $this->version = json_decode(file_get_contents(__DIR__.'/../../composer.json'))->version;
		$this->dev = $this->setupEnv();
		
		!config('cooker.silent') ? $this->info('👨‍🍳 Cooker '.$this->version.' ('.ucfirst($this->env).')'.PHP_EOL) : '';
        
        $this->line('Searching repository...');

        $response = Http::get('https://registry.npmjs.org/'.$this->argument('package'));
        if($response->failed()){
            $this->error('Package not found');
            return;
        }
        $response = $response->object();

        // Convert response to an array (helps with some key names having - in them)
        $responseArray = json_decode(json_encode($response), true);

        // Get the latest version
        $latestVersion = $responseArray['dist-tags']['latest'];
        $targetVersion = $latestVersion; // temp

        $this->line('✨ Found '.$response->name.'@'.$targetVersion.' - '.$response->description);

        // Now grab the script
        $this->line('Installing to '.config('app.name').'...');

        // Grab the script using unpkg
        $script = Http::get('https://unpkg.com/'.$this->argument('package').'@'.$targetVersion);
        if($script->failed()){
            $this->error('Failed to download package');
            return;
        }

        // If the cooker_resources folder doesn't exist, create it
        if (!file_exists(config('cooker.packageManager.packagesPath'))) {
            $this->makeDirectory(config('cooker.packageManager.packagesPath'));
        }


        // Make the package directory
        if (!file_exists(config('cooker.packageManager.packagesPath')).'/'.$this->argument('package')) {
            $this->makeDirectory(config('cooker.packageManager.packagesPath').'/'.$this->argument('package'));
        }

        // Download the script to the package directory
        $this->line('👩‍🔧 Parsing script...');
        $script = $this->compress($script->body(), 'js');
        
        $this->line('📦 Wrapping up...');
        file_put_contents(config('cooker.packageManager.packagesPath').'/'.$this->argument('package').'/'.$targetVersion.'.js', $script);
        
        $this->info('✅ Installed '.$this->argument('package').'@'.$targetVersion.' to '.config('app.name'));
        

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
}