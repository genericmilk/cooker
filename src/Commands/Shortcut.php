<?php

namespace Genericmilk\Cooker\Commands;

use Illuminate\Console\Command;

use Cache;
use Exception;

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
