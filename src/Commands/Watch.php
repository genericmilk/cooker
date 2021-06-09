<?php

namespace Genericmilk\Cooker\Commands;

use Illuminate\Console\Command;

use Less_Parser;
use Carbon\Carbon;
use Cache;

class Watch extends Command
{

    protected $signature = 'cooker:watch';
    protected $description = 'Watch files defined by your cooker ovens for changes and run cooker:cook on change';

	protected $version;

    public $hashs = [];
    public $runBuild = false;

	public function __construct(){
        parent::__construct();
    }
    public function handle(){
        $this->version = json_decode(file_get_contents(__DIR__.'/../../composer.json'))->version;
        !config('cooker.silent') ? $this->info('👨‍🍳 Cooker '.$this->version.PHP_EOL) : '';
        $this->line('👀 Watching for changes. Press Ctrl+C to exit...');
        $this->line("🌟 Show your support at https://github.com/genericmilk/cooker");
        $this->notify('Cooker', 'Cooker started watching for changes...',__DIR__.'/../../cooker.png');

        $this->check();
    }
    private function check(){

        foreach(config('cooker.ovens') as $job){
            $oven = new $job['cooker'](); // Boot the cooker

            
            foreach($job['input'] as $i){
                
                $f2c = resource_path($oven->directory.'/'.$i);
                
                if(isset($this->hashs[$f2c])){
                    // Check if we need to update
                    if($this->hashs[$f2c]!=md5(file_get_contents($f2c))){
                        $this->hashs[$f2c] = md5(file_get_contents($f2c));
                        $this->runBuild = true;
                    }
                }else{
                    // Just register the hash
                    $this->hashs[$f2c] = md5(file_get_contents($f2c));
                }


            }
        }

        if($this->runBuild){
            $this->runBuild = false;
            $this->call('cooker:cook');
        }
        
        sleep(1);
        $this->check();


    }
}