<?php

namespace Genericmilk\Cooker\Stacks;

class VueStack extends Stack
{
    public function name(): string
    {
        return 'vue';
    }

    public function description(): string
    {
        return 'Vue 3 (browser-template build), mounts an App component to #app.';
    }

    public function packages(): array
    {
        return [
            'vue' => '^3.4.0',
        ];
    }

    public function files(): array
    {
        return [
            'resources/js/app.js'            => 'app.js',
            'resources/js/components/App.js' => 'components/App.js',
        ];
    }
}
