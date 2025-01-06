<?php

namespace Genericmilk\Cooker\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\table;
use function Laravel\Prompts\spin;


use function Laravel\Prompts\note;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\error;
use function Laravel\Prompts\alert;


use Genericmilk\Cooker\Engine;

class Cook extends Command
{

    protected $signature = 'cooker:cook {--clear} {--force}';
    protected $description = 'Builds the asset cache';

	protected $version;

	public function __construct(){
        parent::__construct();
    }
    public function handle(): void
	{
        $this->version = json_decode(file_get_contents(__DIR__.'/../../composer.json'))->version;


		note('👨‍🍳 Cooker '.$this->version);

		if($this->option('clear') || $this->option('force')){
			// delete every file in the cache folder
			$response = spin(
				fn () => $this->clearAssetCache(),
				'Clearing asset cache ...'
			);
	
			info('✔️ Asset cache cleared');

			if($this->option('clear')){
				return;
			}
		}

		$response = spin(
			fn () => $this->buildAssetCache(),
			'Building asset cache ...'
		);

		info('✔️ Asset cache built');
	}

	private function buildAssetCache(): void
	{
		$engine = new Engine();
		$engine->output = false;
		foreach(config('cooker.ovens') as $oven){
			$engine->render($oven['file']);
		}
	}

	private function clearAssetCache(): void
	{
		$files = glob(base_path('.cooker/cache/*'));
		foreach($files as $file){
			if(is_file($file)){
				unlink($file);
			}
		}
	}


}