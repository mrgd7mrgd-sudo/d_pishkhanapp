<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

test('health check returns ok status and 200 when all subsystems are healthy', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertOk()
        ->assertJson([
            'status' => 'ok',
            'db' => 'ok',
            'redis' => 'ok',
            'storage' => 'ok',
        ])
        ->assertJsonStructure([
            'status',
            'db',
            'redis',
            'storage',
            'version',
        ]);
});

test('health check returns 503 and unhealthy status when database connection fails', function () {
    // Force DB exception by disconnecting or querying invalid connection
    DB::shouldReceive('select')
        ->once()
        ->andThrow(new RuntimeException('Database connection lost'));

    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(503)
        ->assertJson([
            'status' => 'unhealthy',
            'db' => 'fail',
        ]);
});
