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

        // Validate against the raw template BEFORE substitution so that {word} patterns
        // inside document content (e.g. {Grantor}, {Grantee} in legal contracts) never
        // trigger false-positive "unresolved placeholder" errors.
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $template, $matches);
        foreach ($matches[1] as $placeholder) {
            if (! array_key_exists($placeholder, $variables)) {
                throw new \UnderflowException(
                    "Prompt template '{$name}' has unresolved placeholder: {{$placeholder}}."
                );
            }
        }

        foreach ($variables as $key => $value) {
            $template = str_replace("{{$key}}", (string) $value, $template);
        }

        return $template;
    }
}
