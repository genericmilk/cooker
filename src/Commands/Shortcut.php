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


class Shortcut extends Command
{
	protected $signature = 'cook';

    protected $description = 'Cooker shortcut';

	public function __construct(){
        parent::__construct();
    }
    public function handle(){
        $this->call('cooker:cook');        
	}
}
