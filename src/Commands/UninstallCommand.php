<?php

namespace Genericmilk\Cooker\Commands;

use Genericmilk\Cooker\Cooker;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class UninstallCommand extends Command
{
    protected $signature = 'cooker:uninstall {--force}';

    protected $description = 'Remove Cooker config, the .cooker workspace, and built assets.';

    public function handle(Cooker $cooker): int
    {
        warning('Cooker uninstall removes config, .cooker/, and public/build/. resources/ is left alone.');

        if (!$this->option('force') && !confirm('Continue?', default: false)) {
            note('Cancelled.');
            return self::SUCCESS;
        }

        $base = $cooker->basePath();
        @unlink(config_path('cooker.php'));
        $this->rrmdir($base.'/.cooker');
        $this->rrmdir($base.'/public/build');

        info('Cooker has been uninstalled.');
        return self::SUCCESS;
    }

    protected function rrmdir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $path.'/'.$entry;
            is_dir($full) ? $this->rrmdir($full) : @unlink($full);
        }
        @rmdir($path);
    }
}
