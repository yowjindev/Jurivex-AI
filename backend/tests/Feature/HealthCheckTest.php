<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['status' => 'ok'],
                     'message' => 'OK',
                 ]);
    }
}
