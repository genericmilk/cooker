<?php

namespace Genericmilk\Cooker\Commands;

use Illuminate\Console\Command;

use Cache;
use Exception;

// Cooker subsystems
use Genericmilk\Cooker\Preloads;

// Cooker engines
use Genericmilk\Cooker\Ovens\Js;
use Genericmilk\Cooker\Ovens\Less;
use Genericmilk\Cooker\Ovens\Scss;


class Cook extends Command
{
	protected $signature = 'cooker:cook {--dev} {--prod} {--skipsetup} {--force}';

	protected $dev;
	protected $version;
	protected $env;

	public $hasFailed = false;
	public $allHasSkipped = true;

    protected $description = 'Run all defined cookers';

	public function __construct(){
        parent::__construct();
		$this->version = json_decode(file_get_contents(__DIR__.'/../../composer.json'))->version;		
    }
    public function handle(){
		$this->dev = $this->setupEnv();

		// Check if we have run setup and launch it if we need to
		if(is_null(config('cooker.silent'))){
			if(!$this->option('skipsetup')){
				$this->call('cooker:init');
				return;
			}
		}

		$start = microtime(true); // Start a timer
		!config('cooker.silent') ? $this->info('👨‍🍳 Cooker '.$this->version.' ('.ucfirst($this->env).')'.PHP_EOL) : '';

		// Run ovens
		$table = [];

		foreach(config('cooker.ovens') as $job){

			try{

				if(config('cooker.canSpeedyCook')){
					try{
						$hasDiffs = $this->compareHashTree(public_path('build/'.$job['output'].'.speedy'));
					}catch(Exception $e){
						// Probably no hash tree yet, make one as the diffs (All files need processing)
						$hasDiffs = true;
					}
				}else{
					$hasDiffs = true; // default to true if speedycook is off
				}

				if(!$this->option('force')){
					$hasDiffs = true;
				}


				$oven = new $job['cooker'](); // Boot the cooker

				if($hasDiffs){
					$stamp = $job['stamped'] ? "/* ".$job['output']." Generated by Cooker ".$this->version." (https://github.com/genericmilk/cooker) ::: ".ucfirst($this->env)." build compiled at ".now()." */" : ""; // If stamped is on output the build times
	
					$preloads = Preloads::obtain($job['preload'],$oven); // get the cook job's preloads								
					$libraries = $this->libraries($oven); // Get the preloads from the directory of the job (Pre uniqs)
	
					$appcode = ''; // start a new string
	
					// Add toolbelt if the job allows it
					if($oven->format=='js'){
						if($job['toolbelt']){
							// User wants toolbelt
							$toolbelt = file_get_contents(__DIR__.'/../toolbelt.js');
		
							// Configure the toolbelt
							$toolbelt = str_replace('__isProd__',($this->env=='prod' ? 'true' : 'false'),$toolbelt);
							$toolbelt = str_replace('__cookerVersion__',($this->version),$toolbelt);

							$appcode .= $toolbelt;
						}
					}
	
	
					$appcode .= $oven::cook($job); // cook the job's inputted filtes
					$appcode .= $oven->format=='js' ? $job['namespace'].'.boot();' : ''; // if javascript finish by booting the script
	
					if($this->env=='prod'){
						$appcode = $this->compress($appcode,$oven->format);
					}
					
	
					$o = $stamp . $preloads . $libraries . $appcode;
					
					if (!file_exists(public_path('build'))) {
						mkdir(public_path('build'), 0777, true);
					}
	
					file_put_contents(public_path('build/'.$job['output']),$o); // write o
					$this->allHasSkipped = false;
					$table[] = [
						'🟢',
						$job['name'],
						'OK'
					];
				}else{
					$table[] = [
						'🟠',
						$job['name'],
						'No changes'
					];
				}
				
				
				// Update the speedy
				$hashTree = $this->generateHashTree($oven,$job);

				file_put_contents(public_path('build/'.$job['output'].'.speedy'),$hashTree); // write o

				

			}catch(Exception $e){
				if($this->option('test')){
					throw new Exception($e); // throw an exception to kick out any jobs that have failed
				}else{
					$this->allHasSkipped = false;
					$table[] = [
						'🔴',
						$job['name'],
						$e->getMessage()
					];
					$this->hasFailed = true;
				}
				
				
			}
		}

		$time_elapsed_secs = round(microtime(true) - $start,2);

		// If cooker is not silent or if it is and it has failed, print the table
		if(!config('cooker.silent') || $this->hasFailed){
			$this->table(['','Job', 'Status'],$table);
			$this->line(PHP_EOL."⏱  ".$time_elapsed_secs."s   ✨ Share the love: https://github.com/genericmilk/cooker");
			if(config('cooker.notifications')){
				if($this->allHasSkipped){
					$this->notify('🟠 Cook Skipped' ,'Took '.$time_elapsed_secs.'s',__DIR__.'/../../cooker.png');
				}else{
					$this->notify(($this->hasFailed ? '🔴 Cook Failed' : '🟢 Cooked OK') ,'Took '.$time_elapsed_secs.'s',__DIR__.'/../../cooker.png');
				}

			}
		}
    }

	// Helpers
	private function libraries($oven){
		// Global less libs (All common everywhere)
		try{
			$dir = scandir(resource_path($oven->directory.'/libraries'));
			unset($dir[0]);
			unset($dir[1]);
			if (($key = array_search('.DS_Store', $dir)) !== false) {
				unset($dir[$key]);
			}
			$dir = array_values($dir);
			$libs = '';
			foreach($dir as $lib){
				$libs .= $this->lastLineFormat(file_get_contents(resource_path($oven->directory.'/libraries/'.$lib)),$oven->format);
			}
			return $libs;
		}catch(\Exception $e){
			return null;
		}
	}
	private function compress($input,$type){
		/*
			Squashes files, but only if we're in production
		*/
		if($type=='css'){
				
		}elseif($type=='js'){
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
		}

		$input = $this->lastLineFormat($input,$type);
		return $input;
	}
	private function setupEnv(){
		$dev = config('app.debug');
		if($this->option('dev')){
			$dev = true;
		}
		if($this->option('prod')){
			$dev = false;
		}
		$this->env = $dev ? 'dev' : 'prod';
		return $dev;
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
	private function generateHashTree($oven,$job){
		/*
			Generates a hash tree of all files in the oven
		*/
		$tree = [];

		$dirBase = $oven->directory;
		foreach($job['input'] as $input){
			$tree[resource_path($dirBase.'/'.$input)] = md5_file(resource_path($dirBase.'/'.$input));
		}

		return json_encode($tree);
	}
	private function compareHashTree($speedy){
		$diffs = [];
		$speedy = json_decode(file_get_contents($speedy),true);

		foreach($speedy as $file => $hash){
			if(md5_file($file)!=$hash){
				$diffs[] = $file;
			}
		}

		return count($diffs)>0;
	}
}
