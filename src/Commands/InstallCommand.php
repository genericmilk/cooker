<?php

namespace Genericmilk\Cooker\Commands;

use Genericmilk\Cooker\Cooker;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

class InstallCommand extends Command
{
    protected $signature = 'cooker:install
        {--force : Overwrite existing files}
        {--with=* : Stacks to install (react, vue, tailwind)}
        {--no-interaction-stacks : Skip stack selection prompt}';

    protected $description = 'Bootstrap Cooker — config, recipes, .cooker workspace, optional stacks.';

    public function handle(Cooker $cooker): int
    {
        note('👨‍🍳 Cooker 10');

        if (file_exists(config_path('cooker.php')) && !$this->option('force')) {
            warning('Cooker config already exists. Use --force to reinstall.');
            return self::FAILURE;
        }

        if (!$this->option('force') && !confirm('Install Cooker into this Laravel project?', default: true)) {
            return self::SUCCESS;
        }

        spin(fn () => $this->scaffold($cooker), 'Scaffolding project files');
        info('✔ Scaffolded resources/ and .cooker/');

        spin(fn () => $cooker->toolbox()->esbuild()->ensureInstalled(), 'Downloading esbuild');
        info('✔ esbuild ready');

        $stacks = (array) $this->option('with');
        if (!$stacks && !$this->option('no-interaction-stacks')) {
            $stacks = multiselect(
                label: 'Add a stack? (optional)',
                options: ['react' => 'React 18', 'vue' => 'Vue 3', 'tailwind' => 'Tailwind CSS 4'],
                hint: 'You can install more later with `php artisan cooker:add <stack>`',
                default: [],
            );
        }

        foreach ($stacks as $name) {
            $this->call('cooker:add', ['target' => $name]);
        }

        info('🎉 Cooker is installed.');
        note('Next: drop `@cooker(\'app.js\') @cooker(\'app.css\')` into your Blade and run `php artisan cooker:cook`.');

        return self::SUCCESS;
    }

    protected function scaffold(Cooker $cooker): void
    {
        $this->call('vendor:publish', ['--tag' => 'cooker-config']);

        $base = $cooker->basePath();
        $defaults = __DIR__.'/../Defaults';

        $this->ensureDir($base.'/.cooker');
        $this->ensureDir($base.'/.cooker/cache');
        $this->ensureDir($base.'/.cooker/bin');
        $this->ensureDir($base.'/.cooker/packages');
        $this->ensureDir($base.'/resources/js');
        $this->ensureDir($base.'/resources/css');
        $this->ensureDir($base.'/public/build');

        $cookerJson = $base.'/.cooker/cooker.json';
        if (!is_file($cookerJson) || $this->option('force')) {
            copy($defaults.'/cooker.json', $cookerJson);
        }

        $this->copyIfMissing($defaults.'/recipes/app.js',  $base.'/resources/js/app.js');
        $this->copyIfMissing($defaults.'/recipes/app.css', $base.'/resources/css/app.css');

        $this->updateGitignore($base.'/.gitignore');
    }

    protected function copyIfMissing(string $from, string $to): void
    {
        if (is_file($to) && !$this->option('force')) {
            return;
        }
        if (!is_dir(dirname($to))) {
            mkdir(dirname($to), 0777, true);
        }
        copy($from, $to);
    }

    protected function ensureDir(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    protected function updateGitignore(string $path): void
    {
        $entries = [
            '/.cooker/bin',
            '/.cooker/cache',
            '/.cooker/packages',
            '/public/build',
        ];
        $existing = is_file($path) ? file_get_contents($path) : '';
        $append = '';
        foreach ($entries as $entry) {
            if (!str_contains($existing, $entry)) {
                $append .= $entry."\n";
            }
        }
        if ($append) {
            file_put_contents($path, ($existing ? rtrim($existing)."\n\n" : '')."# Cooker\n".$append, LOCK_EX);
        }
    }
}
