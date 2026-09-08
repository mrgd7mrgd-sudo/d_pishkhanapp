<?php

declare(strict_types=1);

use App\Shared\Http\Middleware\RequestId;

test('validation errors return RFC 7807 problem details structure', function () {
    $response = $this->postJson('/api/test/validation', [
        'email' => 'invalid-email',
    ]);

    $response->assertStatus(422)
        ->assertHeader(RequestId::HEADER_NAME)
        ->assertJsonStructure([
            'type',
            'title',
            'status',
            'code',
            'detail',
            'instance',
            'request_id',
            'errors' => [
                'email',
            ],
        ])
        ->assertJson([
            'status' => 422,
            'code' => 'VALIDATION_ERROR',
            'title' => 'خطای اعتبارسنجی داده‌های ورودی',
        ]);
});

test('404 not found errors return RFC 7807 problem details structure', function () {
    $response = $this->getJson('/api/non-existent-route-for-testing');

    $response->assertStatus(404)
        ->assertHeader(RequestId::HEADER_NAME)
        ->assertJsonStructure([
            'type',
            'title',
            'status',
            'code',
            'detail',
            'instance',
            'request_id',
        ])
        ->assertJson([
            'status' => 404,
            'code' => 'RESOURCE_NOT_FOUND',
        ]);
});

test('request id middleware generates X-Request-Id header if absent', function () {
    $response = $this->getJson('/api');

    $response->assertOk();
    $response->assertHeader(RequestId::HEADER_NAME);
    $headerVal = $response->headers->get(RequestId::HEADER_NAME);
    expect($headerVal)->toBeString()->toStartWith('req_');
});

test('request id middleware preserves incoming X-Request-Id header', function () {
    $customId = 'req_custom_test_123456789';
    $response = $this->withHeader(RequestId::HEADER_NAME, $customId)
        ->getJson('/api');

    $response->assertOk();
    $response->assertHeader(RequestId::HEADER_NAME, $customId);
});
