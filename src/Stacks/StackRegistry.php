<?php

namespace Genericmilk\Cooker\Stacks;

use Genericmilk\Cooker\Cooker;
use RuntimeException;

class StackRegistry
{
    /** @var array<string, class-string<Stack>> */
    protected static array $stacks = [
        'react'    => ReactStack::class,
        'vue'      => VueStack::class,
        'tailwind' => TailwindStack::class,
    ];

    public static function names(): array
    {
        return array_keys(self::$stacks);
    }

    public static function has(string $name): bool
    {
        return isset(self::$stacks[$name]);
    }

    public static function make(string $name, Cooker $cooker): Stack
    {
        if (!self::has($name)) {
            throw new RuntimeException("Unknown stack: $name. Available: ".implode(', ', self::names()));
        }
        $class = self::$stacks[$name];
        return new $class($cooker);
    }
}
