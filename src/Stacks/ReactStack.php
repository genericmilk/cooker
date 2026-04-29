<?php

namespace Genericmilk\Cooker\Stacks;

class ReactStack extends Stack
{
    public function name(): string
    {
        return 'react';
    }

    public function description(): string
    {
        return 'React 18 with JSX, scaffolds an App component mounted to #app.';
    }

    public function packages(): array
    {
        return [
            'react'     => '^18.0.0',
            'react-dom' => '^18.0.0',
        ];
    }

    public function files(): array
    {
        return [
            'resources/js/app.jsx'            => 'app.jsx',
            'resources/js/components/App.jsx' => 'components/App.jsx',
        ];
    }
}
