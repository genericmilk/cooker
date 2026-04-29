<?php

namespace Genericmilk\Cooker\Stacks;

class TailwindStack extends Stack
{
    public function name(): string
    {
        return 'tailwind';
    }

    public function description(): string
    {
        return 'Tailwind CSS 4 via the bundled standalone binary.';
    }

    public function packages(): array
    {
        return [];
    }

    public function files(): array
    {
        return [
            'resources/css/app.css' => 'app.css',
        ];
    }

    public function install(callable $log): void
    {
        parent::install($log);

        $log('  → downloading tailwindcss binary');
        $this->cooker->toolbox()->tailwind()->ensureInstalled();
    }
}
