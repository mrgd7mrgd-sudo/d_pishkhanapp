<?php

declare(strict_types=1);

use App\Integration\Geo\AddressResult;
use App\Integration\Geo\Drivers\FakeDriver;
use App\Integration\Geo\Drivers\NeshanDriver;
use App\Integration\Geo\Enums\MapTheme;
use App\Integration\Geo\GeoPoint;
use App\Integration\Geo\RouteResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();
});

test('neshan driver reverse geocodes coordinates matching official contract fixture', function (): void {
    $apiKey = 'nsh_test_sec_key_123456789';

    Http::fake([
        'https://api.neshan.org/v5/reverse*' => Http::response([
            'status' => 'OK',
            'formatted_address' => 'تهران، میدان ولیعصر، بلوار کشاورز',
            'route_name' => 'بلوار کشاورز',
            'route_type' => 'primary',
            'neighbourhood' => 'ولیعصر',
            'city' => 'تهران',
            'state' => 'استان تهران',
            'place' => 'میدان ولیعصر',
            'district' => 'منطقه ۶',
            'in_traffic_zone' => true,
            'in_odd_even_zone' => false,
            'addresses' => [
                [
                    'formatted' => 'بلوار کشاورز',
                    'neighbourhood' => 'ولیعصر',
                ],
            ],
        ], 200),
    ]);

    $driver = new NeshanDriver($apiKey);
    $result = $driver->reverseGeocode(35.7118, 51.4055);

    expect($result)->toBeInstanceOf(AddressResult::class);
    expect($result->formattedAddress)->toBe('تهران، میدان ولیعصر، بلوار کشاورز');
    expect($result->neighbourhood)->toBe('ولیعصر');
    expect($result->city)->toBe('تهران');
    expect($result->state)->toBe('استان تهران');
    expect($result->inTrafficZone)->toBeTrue();
    expect($result->inOddEvenZone)->toBeFalse();

    Http::assertSent(function ($request) use ($apiKey): bool {
        return $request->hasHeader('Api-Key', $apiKey)
            && str_contains($request->url(), '/v5/reverse')
            && $request['lat'] == 35.7118
            && $request['lng'] == 51.4055;
    });
});

test('neshan driver searches address matching official contract fixture', function (): void {
    $apiKey = 'nsh_test_sec_key_123456789';

    Http::fake([
        'https://api.neshan.org/v1/search*' => Http::response([
            'count' => 1,
            'items' => [
                [
                    'title' => 'میدان ولیعصر',
                    'address' => 'تهران، میدان ولیعصر',
                    'neighbourhood' => 'منطقه ۶، تهران',
                    'region' => 'تهران',
                    'type' => 'square',
                    'category' => 'place',
                    'location' => [
                        'x' => 51.4055,
                        'y' => 35.7118,
                    ],
                ],
            ],
        ], 200),
    ]);

    $driver = new NeshanDriver($apiKey);
    $results = $driver->searchAddress('میدان ولیعصر', 35.7118, 51.4055);

    expect($results)->toHaveCount(1);
    /** @var AddressResult $first */
    $first = $results->first();
    expect($first->formattedAddress)->toBe('تهران، میدان ولیعصر');
    expect($first->latitude)->toBe(35.7118);
    expect($first->longitude)->toBe(51.4055);
    expect($first->place)->toBe('میدان ولیعصر');

    Http::assertSent(function ($request) use ($apiKey): bool {
        return $request->hasHeader('Api-Key', $apiKey)
            && str_contains($request->url(), '/v1/search')
            && $request['term'] === 'میدان ولیعصر';
    });
});

test('neshan driver calculates route matching official contract fixture', function (): void {
    $apiKey = 'nsh_test_sec_key_123456789';

    Http::fake([
        'https://api.neshan.org/v4/direction*' => Http::response([
            'routes' => [
                [
                    'overview_polyline' => [
                        'points' => 'points_mock_polyline_string_xyz',
                    ],
                    'legs' => [
                        [
                            'summary' => 'بزرگراه شهید همت',
                            'distance' => [
                                'value' => 14200,
                                'text' => '۱۴.۲ کیلومتر',
                            ],
                            'duration' => [
                                'value' => 1150,
                                'text' => '۱۹ دقیقه',
                            ],
                            'steps' => [],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $driver = new NeshanDriver($apiKey);
    $route = $driver->calculateRoute(35.7118, 51.4055, 35.7820, 51.3730);

    expect($route)->toBeInstanceOf(RouteResult::class);
    expect($route->distanceMeters)->toBe(14200);
    expect($route->durationSeconds)->toBe(1150);
    expect($route->distanceText)->toBe('۱۴.۲ کیلومتر');
    expect($route->durationText)->toBe('۱۹ دقیقه');
    expect($route->summaryRoute)->toBe('بزرگراه شهید همت');
    expect($route->encodedPolyline)->toBe('points_mock_polyline_string_xyz');
});

test('neshan driver calculates distance matrix for destinations', function (): void {
    $apiKey = 'nsh_test_sec_key_123456789';

    Http::fake([
        'https://api.neshan.org/v4/direction*' => Http::response([
            'routes' => [
                [
                    'legs' => [
                        [
                            'distance' => ['value' => 5000, 'text' => '۵ کیلومتر'],
                            'duration' => ['value' => 600, 'text' => '۱۰ دقیقه'],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $driver = new NeshanDriver($apiKey);
    $origin = new GeoPoint(35.7118, 51.4055);
    $destinations = [
        new GeoPoint(35.7820, 51.3730),
        new GeoPoint(35.7008, 51.3912),
    ];

    $matrix = $driver->distanceMatrix($origin, $destinations);

    expect($matrix)->toHaveCount(2);
    expect($matrix[0]->distanceMeters)->toBe(5000);
    expect($matrix[0]->durationSeconds)->toBe(600);
});

test('api key is never present in any client response or serialized DTO', function (): void {
    $apiKey = 'super_secret_neshan_key_999999999';

    Http::fake([
        'https://api.neshan.org/v5/reverse*' => Http::response([
            'status' => 'OK',
            'formatted_address' => 'تهران، میدان ولیعصر',
            'city' => 'تهران',
        ], 200),
        'https://api.neshan.org/v1/search*' => Http::response([
            'count' => 1,
            'items' => [
                ['title' => 'میدان ولیعصر', 'address' => 'تهران', 'location' => ['x' => 51.4, 'y' => 35.7]],
            ],
        ], 200),
        'https://api.neshan.org/v4/direction*' => Http::response([
            'routes' => [
                ['legs' => [['distance' => ['value' => 1000], 'duration' => ['value' => 100]]]],
            ],
        ], 200),
    ]);

    $driver = new NeshanDriver($apiKey);

    $address = $driver->reverseGeocode(35.7118, 51.4055);
    $addressJson = json_encode($address->toArray());
    expect($addressJson)->not->toContain($apiKey);

    $search = $driver->searchAddress('میدان ولیعصر');
    $searchJson = json_encode($search->toArray());
    expect($searchJson)->not->toContain($apiKey);

    $route = $driver->calculateRoute(35.7118, 51.4055, 35.7820, 51.3730);
    $routeJson = json_encode($route->toArray());
    expect($routeJson)->not->toContain($apiKey);

    $template = $driver->tileUrlTemplate(MapTheme::STANDARD_DAY);
    expect($template)->not->toContain($apiKey);
    expect($template)->toBe('/tiles/standard-day/{z}/{x}/{y}.png');

    $proxyUrl = $driver->getTileProxyUrl(14, 10524, 6472, MapTheme::NESHAN);
    expect($proxyUrl)->not->toContain($apiKey);
    expect($proxyUrl)->toBe('/tiles/neshan/14/10524/6472.png');
});

test('fake driver provides deterministic offline behavior', function (): void {
    $fake = new FakeDriver;

    $valiasr = $fake->reverseGeocode(35.7118, 51.4055);
    expect($valiasr->neighbourhood)->toBe('ولیعصر');
    expect($valiasr->city)->toBe('تهران');
    expect($valiasr->inTrafficZone)->toBeTrue();

    $saadatAbad = $fake->reverseGeocode(35.7820, 51.3730);
    expect($saadatAbad->neighbourhood)->toBe('سعادت‌آباد');

    $search = $fake->searchAddress('انقلاب');
    expect($search)->not->toBeEmpty();
    expect($search->first()->place)->toContain('انقلاب');

    $route = $fake->calculateRoute(35.7118, 51.4055, 35.7820, 51.3730);
    expect($route->distanceMeters)->toBeGreaterThan(5000);
    expect($route->durationSeconds)->toBeGreaterThan(300);

    $tileBytes = $fake->fetchTile(10, 500, 500, MapTheme::STANDARD_DAY);
    expect($tileBytes)->not->toBeNull();
    expect(substr((string) $tileBytes, 1, 3))->toBe('PNG');
});

test('repeated tile proxy requests return X-Cache HIT on second request', function (): void {
    // First request: Cache MISS, fetch and store
    $response1 = $this->get('/v1/tiles/standard-day/14/10524/6472.png');

    $response1->assertOk();
    $response1->assertHeader('Content-Type', 'image/png');
    $response1->assertHeader('X-Cache', 'MISS');
    $response1->assertHeader('Cache-Control', 'immutable, max-age=2592000, public');
    expect(substr($response1->getContent(), 1, 3))->toBe('PNG');

    // Second request: Cache HIT from cache
    $response2 = $this->get('/v1/tiles/standard-day/14/10524/6472.png');

    $response2->assertOk();
    $response2->assertHeader('Content-Type', 'image/png');
    $response2->assertHeader('X-Cache', 'HIT');
    $response2->assertHeader('Cache-Control', 'immutable, max-age=2592000, public');
    expect(substr($response2->getContent(), 1, 3))->toBe('PNG');

    // Route without /v1/ prefix also works identically
    $response3 = $this->get('/tiles/standard-day/14/10524/6472.png');
    $response3->assertOk();
    $response3->assertHeader('X-Cache', 'HIT');
});

test('tile proxy validates theme and coordinates returning proper error codes', function (): void {
    // Invalid theme -> 404
    $responseInvalidTheme = $this->getJson('/v1/tiles/unknown-theme/14/10524/6472.png');
    $responseInvalidTheme->assertStatus(404);

    // Zoom level out of range (> 22) -> 422
    $responseInvalidZoom = $this->getJson('/v1/tiles/standard-day/25/10524/6472.png');
    $responseInvalidZoom->assertStatus(422);
});

test('map themes correctly map prototype themes to Neshan themes', function (): void {
    expect(MapTheme::fromPrototype('standard-day'))->toBe(MapTheme::STANDARD_DAY);
    expect(MapTheme::fromPrototype('voyager'))->toBe(MapTheme::STANDARD_DAY);
    expect(MapTheme::fromPrototype('osm'))->toBe(MapTheme::NESHAN);
    expect(MapTheme::fromPrototype('neshan'))->toBe(MapTheme::NESHAN);
    expect(MapTheme::fromPrototype('positron'))->toBe(MapTheme::DREAMY);
    expect(MapTheme::fromPrototype('dreamy'))->toBe(MapTheme::DREAMY);
    expect(MapTheme::fromPrototype('dark'))->toBe(MapTheme::DREAMY);
});

test('nginx tile proxy configuration contains required caching directives and headers', function (): void {
    $configPath = base_path('../../docker/nginx/tile-proxy.conf');
    expect(file_exists($configPath))->toBeTrue();

    $content = (string) file_get_contents($configPath);

    // Verify proxy cache zone and 30-day TTL
    expect($content)->toContain('proxy_cache tile_cache;');
    expect($content)->toContain('proxy_cache_valid 200 30d;');

    // Verify X-Cache header
    expect($content)->toContain('add_header X-Cache $upstream_cache_status always;');

    // Verify Api-Key header injection server-side
    expect($content)->toContain('proxy_set_header Api-Key $neshan_api_key;');

    // Verify themes mapping
    expect($content)->toContain('standard-day|neshan|dreamy');

    // Verify upstream URL
    expect($content)->toContain('proxy_pass https://api.neshan.org/v4/tiles/raster/$theme/$z/$x/$y.png;');
});
