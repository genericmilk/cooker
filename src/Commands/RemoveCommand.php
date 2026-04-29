<?php

namespace Genericmilk\Cooker\Commands;

use Genericmilk\Cooker\Cooker;
use Genericmilk\Cooker\Stacks\StackRegistry;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;

class RemoveCommand extends Command
{
    protected $signature = 'cooker:remove {target : npm package or stack name to remove}';

    protected $description = 'Remove an npm package or stack.';

    public function handle(Cooker $cooker): int
    {
        $target = (string) $this->argument('target');

        if (StackRegistry::has($target)) {
            $stack = StackRegistry::make($target, $cooker);
            note("Removing stack: $target");
            $stack->uninstall(fn (string $msg) => $this->line($msg));
            info("✔ Stack '$target' removed (scaffolded files left in place — delete manually if desired).");
            return self::SUCCESS;
        }

        try {
            $cooker->installer()->remove($target);
        } catch (\Throwable $e) {
            error($e->getMessage());
            return self::FAILURE;
        }

        info("✔ Removed package: $target");
        return self::SUCCESS;
    }
}
