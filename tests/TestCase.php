<?php

namespace Tests;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // RefreshDatabase menjalankan migration tetapi tidak seeder.
        // Role & permission dibutuhkan oleh hampir semua test Phase 1.
        $this->seed(RolePermissionSeeder::class);
    }
}
