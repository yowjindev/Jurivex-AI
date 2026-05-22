<?php

namespace App\Modules\AI\Prompts;

use App\Modules\AI\Prompts\Contracts\PromptLoaderContract;
use InvalidArgumentException;

class PromptLoader implements PromptLoaderContract
{
    private array $templates;

    public function __construct()
    {
        $this->templates = require __DIR__.'/templates.php';
    }

    public function load(string $name, array $variables = []): string
    {
        if (! array_key_exists($name, $this->templates)) {
            throw new InvalidArgumentException("Prompt template '{$name}' not found.");
        }

        $template = $this->templates[$name];

        foreach ($variables as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }

        return $template;
    }
}
