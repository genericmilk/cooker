<?php

namespace Genericmilk\Cooker;

use Illuminate\Console\Command;

use Less_Parser;
use Carbon\Carbon;
use Cache;

class Build extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
	protected $signature = 'cooker:cook {--dev} {--prod}';
	protected $bar;
	protected $dev;

	protected $version = '4.0.0';

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

		if(!config('cooker.silent')){
			try{
				$this->bar = $this->output->createProgressBar(count(config('cooker.less')) + count(config('cooker.js')) + count(config('cooker.frameworks')));
				$this->bar->start();
			}catch(\Exception $e){

			}

		}
		
		// Less		
		foreach(config('cooker.less') as $job){
			$o = config('cooker.build_stamps.css') ? "/* ".$job['output']." Generated by Cooker v".$this->version." by Genericmilk - Last build at ".Carbon::now()." */" : "";
			$o .= $this->obtainFrameworks('css');
			$o .= $this->less_libr();
			foreach($job['libraries'] as $loclib){
				$o .= file_get_contents(resource_path('less/'.$loclib));
			}
			$p = new Less_Parser();   
			foreach($job['input'] as $input){
				if(file_exists(resource_path('less/'.$input))){
					$p->parseFile(resource_path('less/'.$input));
				}else{
					$this->error(resource_path('less/'.$input).' missing. Unable to mix in this cook session');
				}

			}
			$o .= !$this->dev ? $this->minify_css($p->getCss()) : $p->getCss();

			file_put_contents(public_path('build/'.$job['output']),$o); // write o
			!config('cooker.silent') ? $this->bar->advance() : '';
		}

		// Js
		foreach(config('cooker.js') as $job){
			$o = $this->obtainFrameworks('js');
			$o .= $this->js_libr();
			foreach($job['libraries'] as $loclib){
				$o .= file_get_contents(resource_path('js/'.$loclib));
			}
			foreach($job['input'] as $input){
				if(file_exists(resource_path('js/'.$input))){
					$j = file_get_contents(resource_path('js/'.$input));
					if(!$this->dev){
						$j = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '', $j); // remove js comments
						$j = $this->minify_js($j); // minify
						$j = $this->lastLineFormat($j);
					}
					$o .= $j;
				}else{
					$this->error(resource_path('js/'.$input).' missing. Unable to mix in this cook session');
				}
			}

			$o .= is_null(config('cooker.namespace')) ? 'app' : config('cooker.namespace');
			$o .= '.boot();';

			$comment = config('cooker.build_stamps.js') ? "/* ".$job['output']." Generated by Cooker v".$this->version." by Genericmilk - Last build at ".Carbon::now()." */" : "";
			$o = $comment . $o;
			file_put_contents(public_path('build/'.$job['output']),$o); // write o
			!config('cooker.silent') ? $this->bar->advance() : '';
		}
		if(!config('cooker.silent')){
			$this->bar->finish();
			$this->line(PHP_EOL.PHP_EOL."🚀 All done!");
			$this->line("🌟 Show your support at https://github.com/genericmilk/cooker");
		}
    }
    private function minify_css($css) {
	    $css = preg_replace('/\/\*((?!\*\/).)*\*\//','',$css); // negative look ahead
		$css = preg_replace('/\s{2,}/',' ',$css);
		$css = preg_replace('/\s*([:;{}])\s*/','$1',$css);
		$css = preg_replace('/;}/','}',$css);
		return $css;
	}	
	private function minify_js($input) {
	    if(trim($input) === "") return $input;
	    return preg_replace(
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
	private function less_libr(){
		// Global less libs (All common everywhere)
		try{
			$dir = scandir(resource_path('less/libraries'));
			unset($dir[0]);
			unset($dir[1]);
			if (($key = array_search('.DS_Store', $dir)) !== false) {
				unset($dir[$key]);
			}
			$dir = array_values($dir);
			$libs = '';
			foreach($dir as $lib){
				$libs .= $this->lastLineFormat(file_get_contents(resource_path('less/libraries/'.$lib)));
			}
			return $libs;
		}catch(\Exception $e){
			return null;
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
	private function obtainFrameworks($platform){
		$frameworkList = json_decode(file_get_contents(__DIR__.'/frameworks.json'));
		$o = '';
		foreach(config('cooker.frameworks') as $f){
			foreach($frameworkList as $frameworkOnList){
				$frameworkOnList = (object)$frameworkOnList;
				if($frameworkOnList->type==$platform && $frameworkOnList->name==$f){
					// Matched a valid framework. Check the cache and retrieve that version or grab url
					$cache_name = 'cooker3'.$frameworkOnList->type.$frameworkOnList->name;
					if (Cache::has($cache_name)) {						
						$o .= Cache::get($cache_name);
					}else{
						$download = $this->lastLineFormat(file_get_contents($this->dev ? $frameworkOnList->urlDev : $frameworkOnList->url));
						Cache::put($cache_name, $download, Carbon::today()->addMonths(1));
						$o .= $download;
					}
					if(!config('cooker.silent')){
						try{
							$this->bar->advance();
						}catch(\Exception $e){
							// Bar not defined
						}

					}
				}
			}
		}
		return $o;
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
