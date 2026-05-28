<?php

namespace Tests\Feature\AI;

use App\Modules\AI\Prompts\Contracts\PromptLoaderContract;
use App\Modules\AI\Prompts\PromptLoader;
use InvalidArgumentException;
use Tests\TestCase;

class PromptLoaderTest extends TestCase
{
    private PromptLoaderContract $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loader = new PromptLoader();
    }

    public function test_loads_known_template(): void
    {
        $result = $this->loader->load('document.analyze', ['filename' => 'test.pdf', 'content' => 'test doc']);

        $this->assertStringContainsString('test doc', $result);
        $this->assertStringContainsString('Analyze', $result);
    }

    public function test_substitutes_single_variable(): void
    {
        $result = $this->loader->load('document.summarize', ['content' => 'hello world']);

        $this->assertStringContainsString('hello world', $result);
        $this->assertStringNotContainsString('{content}', $result);
    }

    public function test_throws_for_unknown_template(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Prompt template 'unknown.template' not found.");

        $this->loader->load('unknown.template');
    }

    public function test_resolves_from_container(): void
    {
        $loader = $this->app->make(PromptLoaderContract::class);

        $this->assertInstanceOf(PromptLoader::class, $loader);
    }

    public function test_throws_for_missing_placeholder(): void
    {
        $this->expectException(\UnderflowException::class);
        $this->expectExceptionMessage('has unresolved placeholder');

        $this->loader->load('document.analyze'); // missing 'content' variable
    }

    public function test_loads_extract_risks_template(): void
    {
        $result = $this->loader->load('document.extract_risks', [
            'content'  => 'sample doc',
            'filename' => 'sample.pdf',
        ]);

        $this->assertStringContainsString('sample doc', $result);
        $this->assertStringNotContainsString('{content}', $result);
    }

    public function test_extract_risks_prompt_loads_with_filename_and_content_placeholders(): void
    {
        $loader = app(\App\Modules\AI\Prompts\Contracts\PromptLoaderContract::class);

        $prompt = $loader->load('document.extract_risks', [
            'content'  => 'Summary: A lease agreement between Acme Corp and Widget Inc.',
            'filename' => 'lease_agreement.pdf',
        ]);

        $this->assertStringContainsString('JSON array', $prompt);
        $this->assertStringContainsString('lease_agreement.pdf', $prompt);
        $this->assertStringContainsString('Summary: A lease agreement between Acme Corp and Widget Inc.', $prompt);
        $this->assertStringNotContainsString('{filename}', $prompt);
        $this->assertStringNotContainsString('{content}', $prompt);
    }
}
