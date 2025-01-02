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


use Less_Parser;
use Carbon\Carbon;
use Cache;
use Exception;

class Install extends Command
{

    protected $signature = 'cooker:install {--uninstall}';
    protected $description = 'The Cooker installer';

	protected $dev;
	protected $version;
	protected $env;

	public function __construct(){
        parent::__construct();
    }
    public function handle(): void
	{
        $this->dev = $this->setupEnv();
        $this->version = json_decode(file_get_contents(__DIR__.'/../../composer.json'))->version;

		system('clear');

		note('👨‍🍳 Cooker '.$this->version);



		if($this->option('uninstall')){
			if(is_null(config('cooker.ovens'))){
				error('Cooker is not installed. If you want to install, please run php artisan cooker:install');
				return;
			}else{
				$this->handleUninstall();
				return;
			}
		}else{
			if(!is_null(config('cooker.ovens'))){
				error('Cooker is already installed. If you want to uninstall, please run php artisan cooker:install --uninstall');
				return;
			}
		}
		
		note('We\'re so pleased you\'ve decided to use Cooker. It\'s a great way to manage your assets and keep your project tidy.');
		note('Before we get started, Cooker will need to install some bits to your Laravel project. We\'ll ask you about this in a moment.');

		warning('Please note that Cooker will remove your existing /resources/js and /resources/sass folders and replace them with Cooker\'s own.');
		warning('That includes anything inside of them, so make backups now!');

		$confirmed = confirm(
			label: 'Are you ready for us to start installation?',
			default: false,
			yes: 'Let\'s do this!',
			no: 'Let me do this in a bit',
			hint: 'Cooker needs to be installed to be used.'
		);

		if(!$confirmed){
			system('clear');
			warning('👋 No problem. You can run this command again when you\'re ready.');
			return;
		}


		$response = spin(
			fn () => $this->installCooker(),
			'Installing Cooker ...'
		);



		info('🎉 Cooker has been installed!');
		note('You can now use the @cooker helpers in your blade for app.js and app.less. The asset cache will be built automatically when you load the page');
		note('You can also run php artisan cooker:cook to manually build the asset cache which is useful for production environments.');
		note('If you want to remove Cooker, you can run php artisan cooker:install --uninstall');
		note('Thanks for using Cooker! If you like it, please consider giving us a star on GitHub. It really helps us out!');
		note('https://github.com/genericmilk/cooker');


	}

	public function handleUninstall(): void
	{
		alert('Cooker is currently installed');
		info('If you want to remove Cooker from this project, You will need to confirm the uninstallation process.');

		$confirmed = confirm(
			label: 'Do you want to uninstall Cooker?',
			default: false,
			yes: 'Yes please!',
			no: 'No, I want to keep using Cooker'
		);

		if(!$confirmed){
			warning('👋 No problem. You can run this command if you ever want to uninstall Cooker (We hope not!).');
			return;
		}

		$response = spin(
			fn () => $this->uninstallCooker(),
			'Uninstalling Cooker ...'
		);

		info('👋 Cooker has been uninstalled.');

		note('I hope you enjoyed using Cooker. If you have any feedback, please let us know on GitHub. We\'re always looking to improve!');
		note('My biggest thanks for using Cooker. It really means a lot to me. If you like it, please consider giving us a star on GitHub. It really helps us out!');
		note('If this is a mistake, you can run php artisan cooker:install to install Cooker again (No hard feelings!)');

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
			return;
		}catch(Exception $e){
		}
	}
	private function makeDirectory($f) {
		try{
			mkdir($f);
		}catch(Exception $e){
		}
	}
	private function setupEnv(){
		return config('app.debug');
	}

	// steps
	private function installCooker(){
		$this->call('vendor:publish', [
			'--provider' => 'Genericmilk\Cooker\ServiceProvider'
		]);
		
		// first tidy up the resources folder
		$this->removeDirectory(resource_path('js'),true);
		$this->removeDirectory(resource_path('css'),true);	
		$this->removeDirectory(resource_path('scss'),true);	
		$this->removeDirectory(resource_path('sass'),true);	
		
		// make a .cooker folder in root
		$this->makeDirectory(base_path('.cooker'));
		$this->makeDirectory(base_path('.cooker/cache'));
		$this->makeDirectory(base_path('.cooker/imports'));
		
		
		if(file_exists(base_path('.gitignore'))){
			$giF = file_get_contents(base_path('.gitignore'));
			if (!strpos($giF, '.cooker/*') !== false) {
				$gi = fopen(base_path('.gitignore'), 'a');
				$data = PHP_EOL.'.cooker/*';
				fwrite($gi, $data);
			}

			if (!strpos($giF, '!.cooker/cooker.json') !== false) {
				$gi = fopen(base_path('.gitignore'), 'a');
				$data = PHP_EOL.'!.cooker/cooker.json';
				fwrite($gi, $data);
			}
			
		}

		$this->makeDirectory(resource_path('less'));
		$this->makeDirectory(resource_path('js'));


		// set the app.less file
		$file = fopen(resource_path('less/app.less'),'w');
		fwrite($file,file_get_contents(__DIR__.'/../Defaults/example.less'));
		fclose($file);

		// set the app.js file
		$file = fopen(resource_path('js/app.js'),'w');
		fwrite($file,file_get_contents(__DIR__.'/../Defaults/example.js'));
		fclose($file);

		// make a cooker.json file in .cooker folder
		$file = fopen(base_path('.cooker/cooker.json'),'w');
		fwrite($file,file_get_contents(__DIR__.'/../Defaults/cooker.json'));
		


	}

	private function uninstallCooker(){

		try{
			unlink(config_path('cooker.php'));
		}catch(Exception $e){
			//
		}
	
		$this->removeDirectory(resource_path('js'),false,true);
		$this->removeDirectory(resource_path('scss'),false,true);
		$this->removeDirectory(resource_path('sass'),false,true);
		$this->removeDirectory(base_path('.cooker'),false,true);

		
	}

}