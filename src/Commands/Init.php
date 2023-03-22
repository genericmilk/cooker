<?php

namespace Genericmilk\Cooker\Commands;

use Illuminate\Console\Command;

use Less_Parser;
use Carbon\Carbon;
use Cache;

class Init extends Command
{

    protected $signature = 'cooker:init';
    protected $description = 'Initialises Cooker for your project. Allows the install and uninstall of essential files for cooker to run';

	protected $dev;
	protected $version;
	protected $env;

	public function __construct(){
        parent::__construct();
    }
    public function handle(){
        $this->dev = $this->setupEnv();
        $this->version = json_decode(file_get_contents(__DIR__.'/../../composer.json'))->version;

		!config('cooker.silent') ? $this->info('👨‍🍳 Cooker '.$this->version.PHP_EOL) : '';
		if(is_null(config('cooker.silent'))){

			if ($this->confirm('🔴 Cooker is NOT INSTALLED.'.PHP_EOL.PHP_EOL.'Are you sure you want to install Cooker? This will remove your existing /resources/js and /resources/sass folders and replace them with Cooker\'s own.')) {				
				$this->call('vendor:publish', [
					'--provider' => 'Genericmilk\Cooker\ServiceProvider'
				]);
				
				$this->removeDirectory(resource_path('js'),true);
				$this->removeDirectory(resource_path('css'),true);	
				$this->removeDirectory(resource_path('scss'),true);	
				$this->removeDirectory(resource_path('sass'),true);	
				

				if(file_exists(base_path('.gitignore'))){
					$giF = file_get_contents(base_path('.gitignore'));
					if (!strpos($giF, '/public/build') !== false) {
						$gi = fopen(base_path().'/.gitignore', 'a');
						$data = PHP_EOL.'/public/build';
						fwrite($gi, $data);
						$this->line('✅ Added cooked targets to .gitignore');
					}

					if (!strpos($giF, '/storage/app/cooker_frameworks_cache') !== false) {
						$gi = fopen(base_path().'/.gitignore', 'a');
						$data = PHP_EOL.'/storage/app/cooker_frameworks_cache';
						fwrite($gi, $data);
						$this->line('✅ Added framework cache to .gitignore');
					}

					
					if (!strpos($giF, 'cooker_packages') !== false) {
						$gi = fopen(base_path().'/.gitignore', 'a');
						$data = PHP_EOL.'cooker_packages';
						fwrite($gi, $data);
						$this->line('✅ Added cooker packages to .gitignore');
					}
					
					
				}

				$this->makeDirectory(public_path('build'));

				$this->makeDirectory(storage_path('app/cooker_frameworks_cache'));				
				$this->makeDirectory(resource_path('less'));
				$this->makeDirectory(resource_path('less/libraries'));
				$this->makeDirectory(resource_path('js'));
				$this->makeDirectory(resource_path('js/libraries'));
				$this->makeDirectory(base_path('cooker_packages'));


				$this->info('🔨 Building example files');
				$file = fopen(resource_path('less/app.less'),'w');
				fwrite($file,file_get_contents(__DIR__.'/../example.less'));
				fclose($file);
				$file = fopen(resource_path('js/app.js'),'w');
				fwrite($file,file_get_contents(__DIR__.'/../example.js'));
				fclose($file);

				$this->info('🔨 Building cooker.json');
				$file = fopen(base_path('cooker.json'),'w');
				fwrite($file,file_get_contents(__DIR__.'/../cooker.json'));
				fclose($file);

				$this->info('💚 Cooker Installed OK!');
			}
		}else{
            if ($this->confirm('🟢 Cooker is INSTALLED.'.PHP_EOL.PHP_EOL.'Are you sure you want to remove cooker?'.PHP_EOL.'This will remove any files in resources and public/build amongst other files created for cooker to run.')) {
				unlink(config_path('cooker.php'));
				unlink(base_path('cooker.json'));
				$this->line('🧨 Removed cooker config');
				$this->removeDirectory(resource_path('js'),false,true);
				$this->removeDirectory(resource_path('scss'),false,true);
				$this->removeDirectory(resource_path('sass'),false,true);
				$this->removeDirectory(storage_path('app/cooker_frameworks_cache'),false,true);
				$this->removeDirectory(resource_path('css'),false,true);	
				$this->removeDirectory(resource_path('less'),false,true);	
				$this->removeDirectory(public_path('build'),false,true);
				$this->removeDirectory(base_path('cooker_packages'),false,true);
				
				// Remove from .gitignore				
				$this->info('💙 Cooker Uninstalled OK');
            }
        }
    }
	private function removeDirectory($path,$silent = false,$silentErrors = false) {
		try{
			// The preg_replace is necessary in order to traverse certain types of folder paths (such as /dir/[[dir2]]/dir3.abc#/)
			// The {,.}* with GLOB_BRACE is necessary to pull all hidden files (have to remove or get "Directory not empty" errors)
			$files = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $path).'/{,.}*', GLOB_BRACE);
			foreach ($files as $file) {
			if ($file == $path.'/.' || $file == $path.'/..') { continue; } // skip special dir entries
			is_dir($file) ? $this->removeDirectory($file) : unlink($file);
			}
			rmdir($path);
			$this->line('🗑 Removed '.$path);
			return;
		}catch(\Exception $e){
			if(!$silent && !$silentErrors){
				$this->error('✋ Could not remove '.$path);
			}
		}
	}
	private function makeDirectory($f) {
		try{
			mkdir($f);
			$this->line('📁 Created '.$f);
		}catch(\Exception $e){
			$this->error('✋ Could not create '.$f);
		}
	}
	private function setupEnv(){
		return config('app.debug');
	}
}