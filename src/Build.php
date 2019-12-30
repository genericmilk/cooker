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

		$this->info('👨‍🍳 Cooker by genericmilk (v2.0.0)');
        $parser = new Less_Parser();
        
        if(!is_dir(base_path()."/public/build")){
			mkdir(base_path()."/public/build");
			$this->info('📂 Created new build folder');
		}

		if(file_exists(base_path().'/.gitignore')){
			$giF = file_get_contents(base_path().'/.gitignore');
			if (!strpos($giF, '/public/build') !== false) {
				$gi = fopen(base_path().'/.gitignore', 'a');
				$data = PHP_EOL.'/public/build'.PHP_EOL;
				fwrite($gi, $data);
				$this->info('⛓ Added public folder to gitignore');
			}
		}

		if(!is_dir(base_path().'/resources/less')){
			// Create libraries folder if not exist
            mkdir(base_path().'/resources/less');
		}

		if(!is_dir(base_path().'/resources/js')){
			// Create libraries folder if not exist
            mkdir(base_path().'/resources/js');
		}

		

		if(!is_dir(base_path().'/resources/less/libraries')){
			// Create libraries folder if not exist
            mkdir(base_path().'/resources/less/libraries');
		}

		if(!is_dir(base_path().'/resources/js/libraries')){
			mkdir(base_path().'/resources/js/libraries');
		}

		if(!file_exists(base_path().'/resources/js/build.json')){
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
        }
		
		$LessLibs = $this->LessLibs();



		$this->info('🍽 Cooking less files...');
		foreach(config('cooker.less') as $input => $output){
			if(!file_exists(base_path().'/resources/less/'.$input)){
				$b = fopen(base_path().'/resources/less/'.$input, 'w');
				$data = '// 👨‍🍳 Import your other less files here!';
				fwrite($b, $data);
			}
			$parser->parseFile(base_path().'/resources/less/'.$input);
			$css = $this->minify_css($parser->getCss());
			$css = $LessLibs . $css; // add the libs to this file
			file_put_contents(base_path()."/public/build/".$output,$css);
			$this->info('✅ Cooked '.$input.' 👉 '.$output);			
		}

    
		
        // Now do js        

        

        $this->info('🍔 Cooking javascript files...');
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
	
			file_put_contents(base_path()."/public/build/".$output,$rJS);


			$this->info('✅ Cooked '.$input.' 👉 '.$output);
		}

        $this->info("⭐️ Cooked! Don't forget to star cooker on Github if you found it useful! https://github.com/genericmilk/cooker");

    }
    private function minify_css($input) {
	    if(trim($input) === "") return $input;
	    return preg_replace(
	        array(
	            // Remove comment(s)
	            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
	            // Remove unused white-space(s)
	            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~+]|\s*+-(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
	            // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
	            '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
	            // Replace `:0 0 0 0` with `:0`
	            '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
	            // Replace `background-position:0` with `background-position:0 0`
	            '#(background-position):0(?=[;\}])#si',
	            // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
	            '#(?<=[\s:,\-])0+\.(\d+)#s',
	            // Minify string value
	            '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
	            '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
	            // Minify HEX color code
	            '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
	            // Replace `(border|outline):none` with `(border|outline):0`
	            '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
	            // Remove empty selector(s)
	            '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
	        ),
	        array(
	            '$1',
	            '$1$2$3$4$5$6$7',
	            '$1',
	            ':0',
	            '$1:0 0',
	            '.$1',
	            '$1$3',
	            '$1$2$4$5',
	            '$1$2$3',
	            '$1:0',
	            '$1$2'
	        ),
	    $input);
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
}
