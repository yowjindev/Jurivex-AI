<?php

namespace Tests\Feature\Schema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentAnalysesExtensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_analyses_has_new_columns(): void
    {
        $columns = Schema::getColumnListing('document_analyses');

        $this->assertContains('confidence', $columns);
        $this->assertContains('raw_response', $columns);
        $this->assertContains('parties', $columns);
        $this->assertContains('governing_law', $columns);
    }
}
