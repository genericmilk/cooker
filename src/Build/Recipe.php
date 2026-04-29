<?php

namespace Genericmilk\Cooker\Build;

class Recipe
{
    public function __construct(
        public readonly string $output,
        public readonly string $entry,
        public readonly string $entryAbsolute,
    ) {
    }

    public function entryExtension(): string
    {
        return strtolower(pathinfo($this->entry, PATHINFO_EXTENSION));
    }

    public function outputExtension(): string
    {
        return strtolower(pathinfo($this->output, PATHINFO_EXTENSION));
    }

    public function isStyle(): bool
    {
        return in_array($this->outputExtension(), ['css'], true);
    }

    public function isScript(): bool
    {
        return in_array($this->outputExtension(), ['js', 'mjs'], true);
    }
}
