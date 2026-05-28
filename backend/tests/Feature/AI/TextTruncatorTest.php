<?php

namespace Tests\Feature\AI;

use App\Modules\AI\Utilities\TextTruncator;
use Tests\TestCase;

class TextTruncatorTest extends TestCase
{
    private TextTruncator $truncator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->truncator = new TextTruncator();
    }

    public function test_short_text_is_returned_unchanged(): void
    {
        $text = str_repeat('a', 1000);

        $result = $this->truncator->truncate($text);

        $this->assertSame($text, $result);
    }

    public function test_text_at_exactly_limit_is_returned_unchanged(): void
    {
        $text = str_repeat('a', 80_000);

        $result = $this->truncator->truncate($text);

        $this->assertSame($text, $result);
    }

    public function test_text_over_limit_is_truncated(): void
    {
        $text = str_repeat('a', 100_000);

        $result = $this->truncator->truncate($text);

        $this->assertLessThan(strlen($text), strlen($result));
        $this->assertStringContainsString('[... document truncated for analysis ...]', $result);
    }

    public function test_truncated_text_preserves_beginning_and_end(): void
    {
        $head = str_repeat('H', 60_000);
        $body = str_repeat('B', 30_000);
        $tail = str_repeat('T', 20_000);
        $text = $head . $body . $tail;

        $result = $this->truncator->truncate($text);

        $this->assertStringStartsWith('HHHH', $result);
        $this->assertStringEndsWith('TTTT', $result);
    }

    public function test_needs_truncation_returns_false_for_short_text(): void
    {
        $this->assertFalse($this->truncator->needsTruncation(str_repeat('a', 79_999)));
    }

    public function test_needs_truncation_returns_true_for_long_text(): void
    {
        $this->assertTrue($this->truncator->needsTruncation(str_repeat('a', 80_001)));
    }
}
