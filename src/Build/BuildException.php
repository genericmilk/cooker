<?php

namespace Genericmilk\Cooker\Build;

use RuntimeException;

class BuildException extends RuntimeException
{
    /** @param array<int, string> $hints */
    public function __construct(
        public readonly string $summary,
        public readonly string $rawOutput,
        public readonly array $hints = [],
        public readonly ?string $recipe = null,
    ) {
        parent::__construct($summary);
    }
}
