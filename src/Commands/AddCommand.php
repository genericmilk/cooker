<?php

namespace Genericmilk\Cooker\Commands;

use Genericmilk\Cooker\Cooker;
use Genericmilk\Cooker\Stacks\StackRegistry;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;

class AddCommand extends Command
{
    protected $signature = 'cooker:add {target : npm package or stack name (e.g. react, vue, tailwind, lodash, react@^18)}';

    protected $description = 'Install an npm package or a stack (react / vue / tailwind).';

    public function handle(Cooker $cooker): int
    {
        $target = (string) $this->argument('target');

        if (StackRegistry::has($target)) {
            return $this->installStack($cooker, $target);
        }

        return $this->installPackage($cooker, $target);
    }

    protected function installStack(Cooker $cooker, string $name): int
    {
        $stack = StackRegistry::make($name, $cooker);
        note("👨‍🍳 Adding stack: $name — ".$stack->description());

        try {
            $stack->install(fn (string $msg) => $this->line($msg));
        } catch (\Throwable $e) {
            error($e->getMessage());
            return self::FAILURE;
        }

        info("✔ Stack '{$name}' installed.");
        return self::SUCCESS;
    }

    protected function installPackage(Cooker $cooker, string $target): int
    {
        [$name, $range] = $this->parse($target);
        note("👨‍🍳 Installing package: {$name}@{$range}");

        try {
            $installed = spin(
                fn () => $cooker->installer()->install($name, $range),
                "Resolving and downloading from npm",
            );
        } catch (\Throwable $e) {
            error($e->getMessage());
            return self::FAILURE;
        }

        info('✔ Installed: '.implode(', ', $installed));
        return self::SUCCESS;
    }

    /**
     * Parse name@range. Handles scoped packages like @scope/name@^1.
     */
    protected function parse(string $target): array
    {
        $isScoped = str_starts_with($target, '@');
        $payload  = $isScoped ? substr($target, 1) : $target;
        $parts    = explode('@', $payload, 2);

        $name  = ($isScoped ? '@' : '').$parts[0];
        $range = $parts[1] ?? 'latest';
        return [$name, $range];
    }
}
