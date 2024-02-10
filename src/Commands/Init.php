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

class Init extends Command
{

    protected $signature = 'cooker:init';
    protected $description = 'Initialises Cooker for your project. Allows the install and uninstall of essential files for cooker to run';

	protected $dev;
	protected $version;
	protected $env;

	public function __construct(){
        parent::__construct();
    }
    public function handle(){
        $this->dev = $this->setupEnv();
        $this->version = json_decode(file_get_contents(__DIR__.'/../../composer.json'))->version;

		system('clear');

		note('👨‍🍳 Welcome to Cooker '.$this->version . ' Installer!');

		if(!is_null(config('cooker.silent'))){
			return $this->handleUninstall();
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

		system('clear');

		$engine = select(
			label: 'What CSS engine do you want to use with Cooker?',
			options: ['LESS', 'SCSS', 'CSS'],
			default: 'LESS',
			hint: 'You can change your mind later in the Cooker config file 🤓'
		);

		$frontendCss = select(
			label: 'Do you want to use a CSS framework with Cooker?',
			options: ['Tailwind', 'No thanks'],
			default: 'No thanks',
			hint: 'You can install anything you like into Cooker later! Just check out the documentation 📚'
		);
		
		$frontendJs = select(
			label: 'What JS framework do you want to use with Cooker?',
			options: ['Vue', 'React', 'Angular','Vanilla JS'],
			default: 'Vue',
			hint: 'You can change your mind later in the Cooker config file 🤓'
		);


		note('✨ Great! We\'re ready to install Cooker now.');
		
		note('Take a minute to check over your answers above. If you\'re happy, we\'ll get started. If not, you can cancel.');

		alert('This is also your final notice to make backups of your /resources/js and /resources/sass folders. On Installation, Cooker will remove them and everything inside of them.');

		$confirmed = confirm(
			label: 'Are you ready for us to start installation?',
			default: false,
			yes: 'Let\'s do this!',
			no: 'Let me do this in a bit'
		);

		if(!$confirmed){
			system('clear');
			warning('👋 No problem. You can run this command again when you\'re ready.');
			return;
		}

		$response = spin(
			fn () => $this->installCooker($engine,$frontendCss,$frontendJs),
			'Installing Cooker ...'
		);



		info('🎉 Cooker has been installed!');
		note('You can now run php artisan cooker:watch to start watching your assets. or php artisan cook to build your assets.');
		note('If you want to remove Cooker, you can run php artisan cooker:init again.');
		note('Thanks for using Cooker! If you like it, please consider giving us a star on GitHub. It really helps us out!');
		note('https://github.com/genericmilk/cooker');

	}

	public function handleUninstall(){
		alert('Cooker is already installed.');
		note('You can now run php artisan cooker:watch to start watching your assets. or php artisan cook to build your assets.');

		info('If you want to remove Cooker, You can do so by choosing the uninstall option below.');

		$confirmed = confirm(
			label: 'Do you want to uninstall Cooker?',
			default: false,
			yes: 'Yes please!',
			no: 'No, I want to keep using Cooker'
		);

		if(!$confirmed){
			system('clear');
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

		note('If this is a mistake, you can run php artisan cooker:init to install Cooker again (No hard feelings!)');

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
		}catch(\Exception $e){
		}
	}
	private function makeDirectory($f) {
		try{
			mkdir($f);
		}catch(\Exception $e){
		}
	}
	private function setupEnv(){
		return config('app.debug');
	}

	// steps
	private function installCooker($engine,$frontendCss,$frontendJs){
		$this->call('vendor:publish', [
			'--provider' => 'Genericmilk\Cooker\ServiceProvider'
		]);
		
		$this->removeDirectory(resource_path('js'),true);
		$this->removeDirectory(resource_path('css'),true);	
		$this->removeDirectory(resource_path('scss'),true);	
		$this->removeDirectory(resource_path('sass'),true);	
		

		if(file_exists(base_path('.gitignore'))){
			$giF = file_get_contents(base_path('.gitignore'));
			if (!strpos($giF, '/public/build') !== false) {
				$gi = fopen(base_path().'/.gitignore', 'a');
				$data = PHP_EOL.'/public/build';
				fwrite($gi, $data);
				$this->line('✅ Added cooked targets to .gitignore');
			}

			if (!strpos($giF, '/storage/app/cooker_frameworks_cache') !== false) {
				$gi = fopen(base_path().'/.gitignore', 'a');
				$data = PHP_EOL.'/storage/app/cooker_frameworks_cache';
				fwrite($gi, $data);
				$this->line('✅ Added framework cache to .gitignore');
			}

			
			if (!strpos($giF, 'cooker_packages') !== false) {
				$gi = fopen(base_path().'/.gitignore', 'a');
				$data = PHP_EOL.'cooker_packages';
				fwrite($gi, $data);
				$this->line('✅ Added cooker packages to .gitignore');
			}
			
			
		}

		$this->makeDirectory(public_path('build'));

		$this->makeDirectory(storage_path('app/cooker_frameworks_cache'));				
		$this->makeDirectory(resource_path('less'));
		$this->makeDirectory(resource_path('less/libraries'));
		$this->makeDirectory(resource_path('js'));
		$this->makeDirectory(resource_path('js/libraries'));
		$this->makeDirectory(base_path('cooker_packages'));

		$file = fopen(resource_path('less/app.less'),'w');
		fwrite($file,file_get_contents(__DIR__.'/../example.less'));
		fclose($file);
		$file = fopen(resource_path('js/app.js'),'w');
		fwrite($file,file_get_contents(__DIR__.'/../example.js'));
		fclose($file);

		$file = fopen(base_path('cooker.json'),'w');
		fwrite($file,file_get_contents(__DIR__.'/../cooker.json'));
		fclose($file);


		// open the config file for writing
		$config = file_get_contents(config_path('cooker.php'));

		// replace the engine
		$newEngine = "Genericmilk\Cooker\Ovens\\".ucfirst(strtolower($engine));
		$config = str_replace("'cooker' => 'Genericmilk\Cooker\Ovens\Less'", "'cooker' => '".$newEngine."'", $config);

		$frontendCss = $frontendCss == 'No thanks' ? null : $frontendCss;
		if($frontendCss!=null){
			if($frontendCss=='Tailwind'){
				// prep to add tailwind
				$this->call('cooker:install', [
					'package' => 'tailwindcss',
					'--silent' => true,
					'--skipsetup' => true
				]);
			}
		}

		$frontendJs = $frontendJs == 'Vanilla JS' ? null : $frontendJs;
		if($frontendJs!=null){
			// prep to add frontend js
			if($frontendJs=='Vue'){
				$this->call('cooker:install', [
					'package' => 'vue',
					'--silent' => true,
					'--skipsetup' => true
				]);
			}else if($frontendJs=='React'){
				$this->call('cooker:install', [
					'package' => 'react',
					'--silent' => true,
					'--skipsetup' => true
				]);
			}else if($frontendJs=='Angular'){
				$this->call('cooker:install',[
					'package' => '@angular/core',
					'--silent' => true,
					'--skipsetup' => true
				]);
			}
		}

		// write the config
		file_put_contents(config_path('cooker.php'),$config);

	}

	private function uninstallCooker(){
		try{
			unlink(config_path('cooker.php'));
		}catch(Exception $e){
			//
		}

		try{
			unlink(base_path('cooker.json'));
		}catch(Exception $e){
			//
		}

		$this->removeDirectory(resource_path('js'),false,true);
		$this->removeDirectory(resource_path('scss'),false,true);
		$this->removeDirectory(resource_path('sass'),false,true);
		$this->removeDirectory(storage_path('app/cooker_frameworks_cache'),false,true);
		$this->removeDirectory(resource_path('css'),false,true);	
		$this->removeDirectory(resource_path('less'),false,true);	
		$this->removeDirectory(public_path('build'),false,true);
		$this->removeDirectory(base_path('cooker_packages'),false,true);
		
	}

}