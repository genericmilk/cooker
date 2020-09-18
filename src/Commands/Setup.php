<?php

namespace Genericmilk\Cooker;

use Illuminate\Console\Command;

use Less_Parser;
use Carbon\Carbon;
use Cache;

class Setup extends Command
{

    protected $signature = 'cooker:setup';
	protected $version;
    protected $description = 'The cooker installer/uninstaller';

    public function __construct(){
        parent::__construct();
    }

    public function handle(){
        $this->dev = $this->setupEnv();
        $this->version = json_decode(file_get_contents(__DIR__.'/../composer.json'))->version;

		$env = $this->dev ? 'Dev' : 'Prod';
		!config('cooker.silent') ? $this->info('👨‍🍳 Cooker Installer '.$this->version.' ('.$env.')'.PHP_EOL) : '';
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
			}
		}else{
            if ($this->confirm('Cooker is already installed. Do you need to uninstall it? This will remove all folders and resources that have been built and will return your application to a pre-cooker state')) {
				unlink(config_path('cooker.php'));
				$this->removeDirectory(resource_path('js'));
				$this->removeDirectory(resource_path('sass'));
				$this->removeDirectory(resource_path('css'));	
				$this->removeDirectory(resource_path('less'));	
				$this->removeDirectory(public_path('build'));	
				// Remove from .gitignore				
				$this->info('💙 Cooker has removed all files installed. You can now run composer remove genericmilk/cooker if you want to uninstall it now!');
            }
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
}