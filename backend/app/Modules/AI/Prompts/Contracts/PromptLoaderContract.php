<?php

namespace App\Modules\AI\Prompts\Contracts;

interface PromptLoaderContract
{
    /**
     * Load a named prompt template and interpolate variables.
     * Variable placeholders use {key} syntax.
     */
    public function load(string $name, array $variables = []): string;
}
