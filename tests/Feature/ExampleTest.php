<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Health endpoint harus tersedia tanpa dependensi asset build.
     */
    public function test_health_endpoint_responds(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }
}
