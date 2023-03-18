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


class Install extends Command
{
	protected $signature = 'cooker:install {package}';	
    protected $description = 'Installs a Javascript package into your project using NPM';

    protected $version;

	public function __construct(){
        parent::__construct();
    }
    public function handle(){
        dd(123);
        $this->version = json_decode(file_get_contents(__DIR__.'/../../composer.json'))->version;
		$this->dev = $this->setupEnv();
		
		!config('cooker.silent') ? $this->info('👨‍🍳 Cooker '.$this->version.' ('.ucfirst($this->env).')'.PHP_EOL) : '';
        
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
		if($this->option('dev')){
			$dev = true;
		}
		if($this->option('prod')){
			$dev = false;
		}
		$this->env = $dev ? 'dev' : 'prod';
		return $dev;
	}
}
