<?php

namespace Genericmilk\Cooker\Commands;

use Genericmilk\Cooker\Cooker;
use Illuminate\Console\Command;

class WatchCommand extends Command
{
    protected $signature = 'cooker:watch
        {--no-hmr : Disable the live-reload dev server}';

    protected $description = 'Watch resources/ and rebuild on change. Live-reloads the browser by default.';

    public function handle(Cooker $cooker): int
    {
        $this->info('👨‍🍳 Cooker — watching for changes (Ctrl+C to stop)');

        $watcher = $this->option('no-hmr')
            ? new \Genericmilk\Cooker\Build\Watcher($cooker->builder(), null)
            : $cooker->watcher();

        if ($watcher->devServer()) {
            $this->line('   live reload: '.$watcher->devServer()->url());
        }

        $watcher->run(
            shouldStop: null,
            onBuild: function (string $event, ?\Throwable $err = null, array $changed = []) use ($watcher) {
                if ($event === 'error' && $err) {
                    $this->error($err->getMessage());
                    return;
                }
                $stamp  = date('H:i:s');
                $names  = array_map(fn ($r) => $r->output, $changed);
                $clients = $watcher->devServer()?->clientCount();
                $suffix = $names ? ' ['.implode(', ', $names).']' : '';
                $hmr    = $clients !== null ? " · {$clients} client(s)" : '';
                $this->line("[$stamp] {$event}{$suffix}{$hmr}");
            },
        );

        return self::SUCCESS;
    }
}
