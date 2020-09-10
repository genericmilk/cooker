<?php

namespace Genericmilk\Cooker;

use Illuminate\Console\Command;

use Less_Parser;
use Carbon\Carbon;

class Build extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'build:res';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build resources to the /build/res folder within public';

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
    public function handle()
    {

		$this->info('👨‍🍳 Cooking');

		// Setup
		if(is_null(config('cooker.namespace'))){
			if ($this->confirm('Thanks for installing Cooker! Running setup will remove the /resources/js and /resources/sass folders in order to initialise. Ready to begin the setup?')) {
				$this->call('vendor:publish', [
					'--provider' => 'Genericmilk\Cooker\ServiceProvider'
				]);
				$this->removeDirectory(base_path().'/resources/js');
				$this->removeDirectory(base_path().'/resources/sass');					
				mkdir(base_path()."/public/build");
				if(file_exists(base_path().'/.gitignore')){
					$giF = file_get_contents(base_path().'/.gitignore');
					if (!strpos($giF, '/public/build') !== false) {
						$gi = fopen(base_path().'/.gitignore', 'a');
						$data = PHP_EOL.'/public/build'.PHP_EOL;
						fwrite($gi, $data);
						$this->info('⛓ Added public folder to gitignore');
					}
				}
				mkdir(base_path().'/resources/less');
				mkdir(base_path().'/resources/js');
				mkdir(base_path().'/resources/less/libraries');
				mkdir(base_path().'/resources/js/libraries');

				$b = fopen(base_path().'/resources/js/build.json', 'w');
				$data = '["boot.js"]';
				fwrite($b, $data);
				if(!file_exists(base_path().'/resources/js/boot.js')){
					$b = fopen(base_path().'/resources/js/boot.js', 'w');
					$data = 'var '.config('cooker.namespace').' = {'.PHP_EOL;
					$data .= '	Boot: function(){'.PHP_EOL;
					$data .= '		console.log("👨‍🍳 Welcome to Cooker!");'.PHP_EOL;
					$data .= '	}'.PHP_EOL;
					$data .= '};';
					fwrite($b, $data);
				}				
				$this->info('💚 Installed! Enjoy using cooker');
			}else{
				$this->error('😵 Setup aborted');
				return;
			}
		}



		//$bar = $this->output->createProgressBar(count($users));
		//$bar->start();
		//$bar->advance();
		//$bar->finish();

		// Less		
		foreach(config('cooker.less') as $job){
			$o = config('cooker.build_stamps.css') ? "/* ".$job['output']." Generated by Cooker v3.0.0 by Genericmilk - Last build at ".Carbon::now()." */" : "";
			$o .= $this->less_libr();
			foreach($job['libraries'] as $loclib){
				$o .= file_get_contents(resource_path('less/'.$loclib));
			}
			$p = new Less_Parser();   
			foreach($job['input'] as $input){
				$p->parseFile(resource_path('less/'.$input));
			}
			$o .= env('APP.DEBUG') ? $this->minify_css($p->getCss()) : $p->getCss();

			file_put_contents(public_path('build/'.$job['output']),$o); // write o
			$this->info('✅ Cooked '.$job['output']);
		}

		// Js
		foreach(config('cooker.js') as $job){
			$o = $this->js_libr();
			foreach($job['libraries'] as $loclib){
				$o .= file_get_contents(resource_path('js/'.$loclib));
			}
			foreach($job['input'] as $input){
				$o .= file_get_contents(resource_path('js/'.$input));
			}
			$o .= config('cooker.namespace').'.boot();';
			if(!env('APP_DEBUG')){
				$o = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '', $o); // remove js comments
				$o = $this->minify_js($o); // minify
			}
			$comment = config('cooker.build_stamps.js') ? "/* ".$job['output']." Generated by Cooker v3.0.0 by Genericmilk - Last build at ".Carbon::now()." */" : "";
			$o = $comment . $o;
			file_put_contents(public_path('build/'.$job['output']),$o); // write o
			$this->info('✅ Cooked '.$job['output']);
		}


        $this->info("🚀 All done! Show your support? https://github.com/genericmilk/cooker");
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
		$dir = scandir(resource_path('less/libraries'));
		unset($dir[0]);
		unset($dir[1]);
		if (($key = array_search('.DS_Store', $dir)) !== false) {
			unset($dir[$key]);
		}
		$dir = array_values($dir);
		$libs = '';
		foreach($dir as $lib){
			$libs .= file_get_contents(resource_path('less/libraries/'.$lib));
		}
		return $libs;
	}
	private function js_libr(){
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
			$libs .= file_get_contents(resource_path('js/libraries/'.$lib));
		}
		return $libs;
	}
	
	
	private function removeDirectory($path) {
	    // The preg_replace is necessary in order to traverse certain types of folder paths (such as /dir/[[dir2]]/dir3.abc#/)
	    // The {,.}* with GLOB_BRACE is necessary to pull all hidden files (have to remove or get "Directory not empty" errors)
	    $files = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $path).'/{,.}*', GLOB_BRACE);
	    foreach ($files as $file) {
		if ($file == $path.'/.' || $file == $path.'/..') { continue; } // skip special dir entries
		is_dir($file) ? $this->removeDirectory($file) : unlink($file);
	    }
	    rmdir($path);
	    return;
	}
}
