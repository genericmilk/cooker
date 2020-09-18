<?php

namespace Genericmilk\Cooker;

use Illuminate\Console\Command;

use Less_Parser;
use Carbon\Carbon;
use Cache;

// Cooker subsystems
use Genericmilk\Cooker\Frameworks;

// Cooker engines
use Genericmilk\Cooker\Cookers\Js;
use Genericmilk\Cooker\Cookers\Less;
use Genericmilk\Cooker\Cookers\Scss;
use Genericmilk\Cooker\Cookers\Styl;


class Build extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
	protected $signature = 'cooker:cook {--dev} {--prod}';
	protected $dev;

	protected $version;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all defined cookers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        $this->version = json_decode(file_get_contents(__DIR__.'/../composer.json'))->version;
		$this->dev = $this->setupEnv();

		$env = $this->dev ? 'Dev' : 'Prod';
		!config('cooker.silent') ? $this->info('👨‍🍳 Cooker '.$this->version.' ('.$env.')'.PHP_EOL) : '';

		// Setup
		if(is_null(config('cooker.namespace'))){
			if ($this->confirm('Thanks for installing Cooker! Running setup will remove the /resources/js and /resources/sass folders in order to initialise. Ready to begin the setup?')) {
				$this->call('vendor:publish', [
					'--provider' => 'Genericmilk\Cooker\ServiceProvider'
				]);
				$this->removeDirectory(resource_path('js'));
				$this->removeDirectory(resource_path('sass'));
				$this->removeDirectory(resource_path('css'));	
				try{
					mkdir(public_path('build'));
				}catch(\Exception $e){
					$this->error('✋ Could not create build folder. Assuming already exists?');
				}

				if(file_exists(base_path('.gitignore'))){
					$giF = file_get_contents(base_path('.gitignore'));
					if (!strpos($giF, '/public/build') !== false) {
						$gi = fopen(base_path().'/.gitignore', 'a');
						$data = PHP_EOL.'/public/build'.PHP_EOL;
						fwrite($gi, $data);
						$this->info('⛓ Added cooked targets to .gitignore');
					}
				}
				mkdir(resource_path('less'));
				mkdir(resource_path('less/libraries'));
				mkdir(resource_path('js'));
				mkdir(resource_path('js/libraries'));
				if(!file_exists(resource_path('js/app.js'))){
					$b = fopen(resource_path('js/app.js'), 'w');
					$data = 'var app = {'.PHP_EOL;
					$data .= '	boot: function(){'.PHP_EOL;
					$data .= '		alert("Cooker is ready and rocking!");'.PHP_EOL;
					$data .= '	}'.PHP_EOL;
					$data .= '};';
					fwrite($b, $data);
				}	
				if(!file_exists(resource_path('less/app.less'))){
					$b = fopen(resource_path('less/app.less'), 'w');
					$data = '// Write your less here or extend it using config.cooker!';
					fwrite($b, $data);
				}	
				
				$this->info('💚 Installed! Enjoy using cooker! To get started, run php artisan build:res again');
				return; // Die here
			}else{
				$this->error('😵 Setup aborted');
				return;
			}
		}


		// Less		
		foreach(config('cooker.cookers') as $job){
			$cooker = new $job['cooker']($job);
			Frameworks::obtain();
		}

		// Js
		foreach(config('cooker.js') as $job){
			Js::cook($job);
		}

		if(!config('cooker.silent')){
			$this->line(PHP_EOL.PHP_EOL."🚀 All done!");
			$this->line("🌟 Show your support at https://github.com/genericmilk/cooker");
		}
    }

	private function js_libr(){
		try{
			// Global js libs (All common everywhere)
			$dir = scandir(resource_path('js/libraries'));
			unset($dir[0]);
			unset($dir[1]);
			if (($key = array_search('.DS_Store', $dir)) !== false) {
				unset($dir[$key]);
			}
			$dir = array_values($dir);
			$libs = '';
			foreach($dir as $lib){
				$libs .= $this->lastLineFormat(file_get_contents(resource_path('js/libraries/'.$lib)));
			}
			return $libs;
		}catch(\Exception $e){
			return null;
		}
	}
	private function removeDirectory($path) {
		try{
	    // The preg_replace is necessary in order to traverse certain types of folder paths (such as /dir/[[dir2]]/dir3.abc#/)
	    // The {,.}* with GLOB_BRACE is necessary to pull all hidden files (have to remove or get "Directory not empty" errors)
	    $files = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $path).'/{,.}*', GLOB_BRACE);
	    foreach ($files as $file) {
		if ($file == $path.'/.' || $file == $path.'/..') { continue; } // skip special dir entries
		is_dir($file) ? $this->removeDirectory($file) : unlink($file);
	    }
	    rmdir($path);
		return;
		}catch(\Exception $e){
		}
	}
	private function setupEnv(){
		$dev = config('app.debug');
		if($this->option('dev')){
			$dev = true;
		}
		if($this->option('prod')){
			$dev = false;
		}
		return $dev;
	}
	private function lastLineFormat($input){
		if(substr($input, -1)!=';'){
			$input = $input.';';
		}
		return $input;
	}
}
