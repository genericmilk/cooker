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



        $parser = new Less_Parser();
        
        if(!is_dir(base_path()."/public/build")){
			mkdir(base_path()."/public/build");
		}


		$this->info('🍽 Cooking Less');

        if(!file_exists(base_path().'/resources/less/app.less')){
            $this->error('app.less not found. Ensure your less index is setup in resources/less');
            return;
        }


        if(!is_dir(base_path().'/resources/less/libraries')){
            mkdir(base_path().'/resources/less/libraries');
        }
        

		$parser->parseFile(base_path().'/resources/less/app.less');
        $css = $this->minify_css($parser->getCss());
		
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

		$css = $Libs . $css;

		
		file_put_contents(base_path()."/public/build/app.css",$css);
		$this->info('✅ Successfully generated app.less 👉 app.css');
        
        // Now do js        

        if(!file_exists(base_path().'/resources/js/build.json')){
            $this->error('build.json not found. Ensure your js index is setup in resources/js');
            return;
        }

        $this->info('🍔 Cooking '.config('cooker.namespace').'.js');
        
		$JsObject = json_decode(file_get_contents(base_path().'/resources/js/build.json'));
		$rJS = '';
		
        foreach($JsObject as $File){
            $rJS .= file_get_contents(base_path().'/resources/js/'.$File);
        }
		$rJS .= config('cooker.namespace').'.Boot();'; // Boot the script        
		
		if(!env('APP_DEBUG')){
			$rJS = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '', $rJS); // remove js comments
			$rJS = $this->minify_js($rJS); // minify
		}


        if(!is_dir(base_path().'/resources/js/libraries')){
            mkdir(base_path().'/resources/js/libraries');
        }

		// Scan libs
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

		file_put_contents(base_path()."/public/build/app.js",$rJS);
		$this->info('✅ Successfully generated app.js 👉 app.js');		

        $this->info('🙌 Done!');

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
}
