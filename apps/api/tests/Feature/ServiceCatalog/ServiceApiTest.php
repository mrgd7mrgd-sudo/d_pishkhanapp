<?php

declare(strict_types=1);

namespace Tests\Feature\ServiceCatalog;

use App\Modules\ServiceCatalog\Database\Seeders\DocumentTypeSeeder;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceCategorySeeder;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceSeeder;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        ServiceCategorySeeder::class,
        DocumentTypeSeeder::class,
        ServiceSeeder::class,
    ]);
});

it('matches Architecture §5.6 Example 3 response shape for services endpoint (TASK-041-T)', function (): void {
    $response = $this->getJson('/api/v1/services?filter[category_id]=identity&limit=5');

    $response->assertStatus(200)
        ->assertHeader('Cache-Control', 'max-age=3600, public, stale-while-revalidate=86400')
        ->assertHeader('ETag');

    $data = $response->json('data');
    expect($data)->toBeArray()->not->toBeEmpty();

    $item = $data[0];

    // Verify all exact contract fields from Architecture §5.6 Example 3
    expect($item)->toHaveKeys([
        'id',
        'title',
        'slug',
        'category',
        'tags',
        'description',
        'requirements',
        'required_documents',
        'estimated_days',
        'fee_rials',
        'department',
        'is_popular',
        'is_new',
        'image',
        'requires_in_person',
        'supports_delivery',
    ]);

    expect($item['category'])->toHaveKeys(['id', 'title', 'color', 'icon'])
        ->and($item['estimated_days'])->toHaveKeys(['min', 'max', 'label'])
        ->and($item['image'])->toHaveKeys(['avif', 'webp', 'width', 'height', 'blurhash']);

    expect($item['fee_rials'])->toBeInt()
        ->and($item['tags'])->toBeArray()
        ->and($item['requirements'])->toBeArray()
        ->and($item['required_documents'])->toBeArray()
        ->and($item['requires_in_person'])->toBeBool()
        ->and($item['supports_delivery'])->toBeBool();

    if (! empty($item['required_documents'])) {
        $doc = $item['required_documents'][0];
        expect($doc)->toHaveKeys(['code', 'title', 'is_mandatory', 'accepts'])
            ->and($doc['is_mandatory'])->toBeBool()
            ->and($doc['accepts'])->toBeArray();
    }

    $meta = $response->json('meta');
    expect($meta)->toHaveKeys(['next_cursor', 'total_estimate'])
        ->and($meta['total_estimate'])->toBeGreaterThan(0);
});

it('supports ETag caching and returns 304 Not Modified when ETag matches (TASK-041-T)', function (): void {
    $initialResponse = $this->getJson('/api/v1/services?limit=10');
    $initialResponse->assertStatus(200);

    $etag = $initialResponse->headers->get('ETag');
    expect($etag)->not->toBeEmpty();

    // Send conditional request with If-None-Match
    $conditionalResponse = $this->withHeaders([
        'If-None-Match' => $etag,
    ])->getJson('/api/v1/services?limit=10');

    $conditionalResponse->assertStatus(304);
    expect($conditionalResponse->getContent())->toBeEmpty();

    // Send with modified ETag -> should return 200
    $mismatchedResponse = $this->withHeaders([
        'If-None-Match' => '"outdated-etag-12345"',
    ])->getJson('/api/v1/services?limit=10');

    $mismatchedResponse->assertStatus(200);
});

it('paginates services cleanly using cursor pagination without duplicate or missed records (TASK-041-T)', function (): void {
    $pageSize = 15;
    $seenIds = [];

    // Page 1
    $response1 = $this->getJson("/api/v1/services?limit={$pageSize}");
    $response1->assertStatus(200);

    $page1Items = $response1->json('data');
    expect(count($page1Items))->toBe($pageSize);

    foreach ($page1Items as $item) {
        $seenIds[$item['id']] = true;
    }

    $nextCursor = $response1->json('meta.next_cursor');
    expect($nextCursor)->not->toBeNull();

    // Page 2
    $response2 = $this->getJson("/api/v1/services?limit={$pageSize}&cursor={$nextCursor}");
    $response2->assertStatus(200);

    $page2Items = $response2->json('data');
    expect(count($page2Items))->toBe($pageSize);

    // Verify no duplicates between Page 1 and Page 2
    foreach ($page2Items as $item) {
        expect(isset($seenIds[$item['id']]))->toBeFalse();
        $seenIds[$item['id']] = true;
    }

    // Page 3
    $nextCursor2 = $response2->json('meta.next_cursor');
    expect($nextCursor2)->not->toBeNull();

    $response3 = $this->getJson("/api/v1/services?limit={$pageSize}&cursor={$nextCursor2}");
    $response3->assertStatus(200);

    $page3Items = $response3->json('data');
    foreach ($page3Items as $item) {
        expect(isset($seenIds[$item['id']]))->toBeFalse();
        $seenIds[$item['id']] = true;
    }

    expect(count($seenIds))->toBe(45);
});

it('executes services list query without N+1 problem (TASK-041-T)', function (): void {
    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->getJson('/api/v1/services?limit=20');
    $response->assertStatus(200);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Query count should be strictly bounded (e.g. <= 5 queries: count, main query, category relation, requiredDocs, documentTypes)
    // Regardless of fetching 5, 20, or 50 records!
    expect(count($queries))->toBeLessThanOrEqual(5);
});

it('filters services by category_id and tag properly (TASK-041-T)', function (): void {
    // 1. Filter by category
    $responseCat = $this->getJson('/api/v1/services?filter[category_id]=identity');
    $responseCat->assertStatus(200);
    $itemsCat = $responseCat->json('data');
    expect($itemsCat)->not->toBeEmpty();
    foreach ($itemsCat as $item) {
        expect($item['category']['id'])->toBe('identity');
    }

    // 2. Filter by tag
    $responseTag = $this->getJson('/api/v1/services?filter[tag]=in-person');
    $responseTag->assertStatus(200);
    $itemsTag = $responseTag->json('data');
    expect($itemsTag)->not->toBeEmpty();
    foreach ($itemsTag as $item) {
        expect($item['tags'])->toContain('in-person');
    }
});

it('searches services with Persian normalized text (TASK-041-T)', function (): void {
    // Query with Arabic Yeh/Kaf: "كارت ملي"
    $response1 = $this->getJson('/api/v1/services?q='.urlencode('كارت ملي'));
    $response1->assertStatus(200);
    $results1 = $response1->json('data');

    // Query with Persian Yeh/Kaf: "کارت ملی"
    $response2 = $this->getJson('/api/v1/services?q='.urlencode('کارت ملی'));
    $response2->assertStatus(200);
    $results2 = $response2->json('data');

    expect($results1)->not->toBeEmpty()
        ->and($results2)->not->toBeEmpty()
        ->and(count($results1))->toBe(count($results2));
});

it('retrieves a single service by slug with 200 or returns 404 RFC 7807 when not found (TASK-041-T)', function (): void {
    /** @var Service $firstService */
    $firstService = Service::query()->firstOrFail();

    $response = $this->getJson("/api/v1/services/{$firstService->slug}");
    $response->assertStatus(200)
        ->assertHeader('ETag')
        ->assertHeader('Cache-Control', 'max-age=3600, public, stale-while-revalidate=86400');

    $data = $response->json('data');
    expect($data['id'])->toBe($firstService->id)
        ->and($data['slug'])->toBe($firstService->slug)
        ->and($data['title'])->toBe($firstService->title);

    // 404 Not Found
    $notFound = $this->getJson('/api/v1/services/non-existent-service-slug-xyz');
    $notFound->assertStatus(404)
        ->assertJsonStructure(['type', 'title', 'status', 'detail', 'code'])
        ->assertJson([
            'status' => 404,
            'code' => 'SERVICE_NOT_FOUND',
        ]);
});

it('lists all categories with dynamic services_count (TASK-041-T)', function (): void {
    $response = $this->getJson('/api/v1/categories');
    $response->assertStatus(200)
        ->assertHeader('ETag')
        ->assertHeader('Cache-Control', 'max-age=3600, public, stale-while-revalidate=86400');

    $data = $response->json('data');
    expect($data)->toBeArray()->toHaveCount(10);

    $cat = $data[0];
    expect($cat)->toHaveKeys([
        'id',
        'title',
        'short_title',
        'icon_name',
        'color',
        'badge',
        'description',
        'services_count',
        'sort_order',
    ])
        ->and($cat['services_count'])->toBeInt()->toBeGreaterThanOrEqual(0);

    $identityCat = collect($data)->firstWhere('id', 'identity');
    expect($identityCat)->not->toBeNull()
        ->and($identityCat['services_count'])->toBeGreaterThanOrEqual(1);
});

it('lists all document types (TASK-041-T)', function (): void {
    $response = $this->getJson('/api/v1/document-types');
    $response->assertStatus(200)
        ->assertHeader('ETag')
        ->assertHeader('Cache-Control', 'max-age=3600, public, stale-while-revalidate=86400');

    $data = $response->json('data');
    expect($data)->toBeArray()->toHaveCount(25);

    $doc = $data[0];
    expect($doc)->toHaveKeys([
        'code',
        'title',
        'description',
        'accepted_mimes',
        'requires_original',
        'validity_months',
    ])
        ->and($doc['accepted_mimes'])->toBeArray()->not->toBeEmpty();
});
