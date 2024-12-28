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

class BuildCache extends Command
{

    protected $signature = 'cooker:cook';
    protected $description = 'Builds the asset cache';

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

		note('👨‍🍳 Welcome to the Cooker Version '.$this->version . ' Installer!');


	}


}