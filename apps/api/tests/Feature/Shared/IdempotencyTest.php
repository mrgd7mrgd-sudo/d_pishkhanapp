<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('idempotent requests return cached response and execute once', function () {
    $idempotencyKey = 'test-key-uuid-12345678';
    $payload = ['amount_rials' => 500000];

    $response1 = $this->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson('/api/test/payment', $payload);

    $response1->assertOk()
        ->assertJson([
            'status' => 'paid',
            'execution_count' => 1,
            'amount_rials' => 500000,
        ]);

    $this->assertDatabaseHas('idempotency_keys', [
        'key' => $idempotencyKey,
        'response_status' => 200,
    ]);

    $response2 = $this->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson('/api/test/payment', $payload);

    $response2->assertOk()
        ->assertJson([
            'status' => 'paid',
            'execution_count' => 1,
            'amount_rials' => 500000,
        ]);
});

test('reused idempotency key with different payload returns 409 conflict', function () {
    $idempotencyKey = 'test-key-conflict-87654321';

    $response1 = $this->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson('/api/test/payment', ['amount_rials' => 100000]);

    $response1->assertOk();

    $response2 = $this->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson('/api/test/payment', ['amount_rials' => 200000]);

    $response2->assertStatus(409)
        ->assertJson([
            'code' => 'IDEMPOTENCY_KEY_PAYLOAD_MISMATCH',
        ]);
});

test('requests without idempotency key execute normally without caching', function () {
    $response1 = $this->postJson('/api/test/payment', ['amount_rials' => 300000]);
    $response1->assertOk();

    $response2 = $this->postJson('/api/test/payment', ['amount_rials' => 300000]);
    $response2->assertOk();

    expect($response2->json('execution_count'))->toBeGreaterThan($response1->json('execution_count'));
});
