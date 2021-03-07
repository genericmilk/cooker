<?php

namespace Genericmilk\Cooker\Commands;

use Illuminate\Console\Command;

use Cache;

// Cooker subsystems
use Genericmilk\Cooker\Preloads;

// Cooker engines
use Genericmilk\Cooker\Ovens\Js;
use Genericmilk\Cooker\Ovens\Less;
use Genericmilk\Cooker\Ovens\Scss;


class Build extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
	protected $signature = 'cooker:cook {--dev} {--prod} {--skipsetup}';

	protected $dev;
	protected $version;
	protected $env;

    protected $description = 'Run all defined cookers';

	public function __construct(){
        parent::__construct();
    }
    public function handle(){
        $this->version = json_decode(file_get_contents(__DIR__.'/../../composer.json'))->version;
		$this->dev = $this->setupEnv();
		// Setup requirement
		if(is_null(config('cooker.silent'))){
			if(!$this->option('skipsetup')){
				$this->call('cooker:setup');
				return;
			}
		}
		$start = microtime(true);

		!config('cooker.silent') ? $this->info('👨‍🍳 Cooker '.$this->version.' ('.ucfirst($this->env).')') : '';

		// Run ovens
		$t = [];
		foreach(config('cooker.ovens') as $job){
			$oven = new $job['cooker']();			
			$stamp = $job['stamped'] ? "/* ".$job['output']." Generated by Cooker v".$this->version." by Genericmilk (".ucfirst($this->env).") - Last build at ".now()." */" : "";
			$frameworks = Preloads::obtain($job['preload'],$oven);

			$appcode = $oven::cook($job); // custom resources			
			$appcode .= $oven->format=='js' ? $job['namespace'].'.boot();' : '';
			$appcode = $this->compress($appcode,$oven->format);
			$o = $stamp . $frameworks . $libraries . $appcode;
			file_put_contents(public_path('build/'.$job['output']),$o); // write o
			$t[] = [
				$job['cooker'],
				$job['output']
			];
		}
		$time_elapsed_secs = round(microtime(true) - $start,2);

		if(!config('cooker.silent')){
			$this->table(['Oven', 'Output'],$t);
			$this->line("🌟 Show your support at https://github.com/genericmilk/cooker");
			$this->notify('Cooker', 'Cooker finished cooking in '.$time_elapsed_secs.'s',__DIR__.'/../../cooker.png');
		}
    }
	private function libraries($path){
		// Global less libs (All common everywhere)
		try{
			$dir = scandir(resource_path($path));
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
	private function compress($input,$type){
		if($type=='css'){
			$input = preg_replace('/\/\*((?!\*\/).)*\*\//','',$input); // negative look ahead
			$input = preg_replace('/\s{2,}/',' ',$input);
			$input = preg_replace('/\s*([:;{}])\s*/','$1',$input);
			$input = preg_replace('/;}/','}',$input);			
		}elseif($type=='js'){
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
		$input = $this->lastLineFormat($input);
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
	private function lastLineFormat($input){
		if(substr($input, -1)!=';'){
			$input = $input.';';
		}
		return $input;
	}
}