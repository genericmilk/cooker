<?php

namespace Genericmilk\Cooker;

use Illuminate\Console\Command;

use Less_Parser;

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

		$this->info('👨‍🍳 Cooker 3 // Genericmilk');

		// Setup
		if(is_null(config('cooker.namespace'))){
			if ($this->confirm('Thanks for installing Cooker! Running setup will remove the /resources/js and /resources/sass folders in order to initialise. Ready to begin the setup?')) {
				$this->call('vendor:publish', [
					'--provider' => 'Genericmilk\Cooker\ServiceProvider'
				]);
				$this->removeDirectory(base_path().'/resources/js');
				$this->removeDirectory(base_path().'/resources/sass');					
				$this->info('🗑 Removed old ');
				mkdir(base_path()."/public/build");
				$this->info('📂 Created new build folder');
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
				$this->info('💚 Initial setup done! Proceeding to the kitchen!');
			}
		}else{
			$this->error('😵 Setup aborted');
			return;
		}

		// Less		
		$LessLibs = $this->LessLibs();
		$this->info('🍔 Cooking less...');
		foreach(config('cooker.less') as $input => $output){
			if(!file_exists(base_path().'/resources/less/'.$input)){
				$b = fopen(base_path().'/resources/less/'.$input, 'w');
				$data = '// 👨‍🍳 Import your other less files here!';
				fwrite($b, $data);
			}
			$parser = new Less_Parser();        
			$parser->parseFile(base_path().'/resources/less/'.$input);
			$css = $this->minify_css($parser->getCss());
			$comment = "/* ".$output." Generated by Cooker by Genericmilk - Last build at ".time()." */";
			$css = $comment . $LessLibs . $css; // add the libs to this file
			file_put_contents(base_path()."/public/build/".$output,$css);
			$this->info('✅ Cooked '.$input.' 👉 '.$output);			
		}

		// JS
        $this->info('🍟 Cooking js...');
		foreach(config('cooker.js') as $input => $output){
			if(!file_exists(base_path().'/resources/js/'.$input)){
				$this->error($input.' not found. Ensure this is a valid build json and is setup in resources/js');
				return;
			}
			$JsObject = json_decode(file_get_contents(base_path().'/resources/js/'.$input));
			$rJS = '';
			foreach($JsObject as $File){
				$rJS .= file_get_contents(base_path().'/resources/js/'.$File);
			}
			$rJS .= config('cooker.namespace').'.Boot();'; // Boot the script        
			if(!env('APP_DEBUG')){
				$rJS = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '', $rJS); // remove js comments
				$rJS = $this->minify_js($rJS); // minify
			}

			$LibFolder = scandir(base_path().'/resources/js/libraries');
			unset($LibFolder[0]);
			unset($LibFolder[1]);
			if (($key = array_search('.DS_Store', $LibFolder)) !== false) {
				unset($LibFolder[$key]);
			}
			$LibFolder = array_values($LibFolder);
			$Libs = '';
			foreach($LibFolder as $Lib){
				$Libs .= file_get_contents(base_path().'/resources/js/libraries/'.$Lib); // Insert jQuery
			}
	
			$rJS = $Libs . $rJS;
	
			$rJS = "/* ".$output." Generated by Cooker v3.0.0 by Genericmilk - Last build at ".time()." */" .$rJS;

			file_put_contents(base_path()."/public/build/".$output,$rJS);
			$this->info('✅ Cooked '.$input.' 👉 '.$output);
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
	private function LessLibs(){
		$LibFolder = scandir(base_path().'/resources/less/libraries');
		unset($LibFolder[0]);
		unset($LibFolder[1]);
		if (($key = array_search('.DS_Store', $LibFolder)) !== false) {
			unset($LibFolder[$key]);
		}
		$LibFolder = array_values($LibFolder);
		$Libs = '';
		foreach($LibFolder as $Lib){
			$Libs .= file_get_contents(base_path().'/resources/less/libraries/'.$Lib);
		}
		return $Libs;
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
