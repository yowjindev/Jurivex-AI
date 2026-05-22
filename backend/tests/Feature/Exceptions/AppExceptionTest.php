<?php

namespace Tests\Feature\Exceptions;

use App\Modules\Documents\Models\Document;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppExceptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('s3');
        Queue::fake();
    }

    public function test_document_not_found_returns_404_json(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->getJson('/api/v1/documents/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Document not found.',
            ]);
    }

    public function test_delete_by_staff_returns_403_json(): void
    {
        $user = User::factory()->create();
        $user->assignRole('staff');

        $document = Document::factory()->create([
            'organization_id' => $user->organization_id,
            'uploaded_by'     => $user->id,
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/documents/{$document->id}")
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only admins and managers can delete documents.',
            ]);
    }

    public function test_compliance_flag_not_found_returns_404_json(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->patchJson('/api/v1/compliance/flags/00000000-0000-0000-0000-000000000000/resolve')
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Compliance flag not found.',
            ]);
    }
}
