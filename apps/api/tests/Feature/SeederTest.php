<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Database\Seeders\OfficeSeeder;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeMedal;
use App\Modules\OfficeNetwork\Domain\Models\OfficeServiceCoverage;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSpecialty;
use App\Modules\ServiceCatalog\Database\Seeders\DocumentTypeSeeder;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceCategorySeeder;
use App\Modules\ServiceCatalog\Database\Seeders\ServiceSeeder;
use App\Modules\ServiceCatalog\Domain\Models\DocumentType;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use App\Modules\ServiceCatalog\Domain\Models\ServiceRequiredDoc;
use App\Shared\Crypto\EnvelopeEncryptor;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

it('seeds all reference entities with exact counts matching architecture specification (§6.9, TASK-040-T)', function (): void {
    $this->seed([
        RoleSeeder::class,
        ProvinceSeeder::class,
        ServiceCategorySeeder::class,
        DocumentTypeSeeder::class,
        ServiceSeeder::class,
        OfficeSeeder::class,
    ]);

    // 1. Verify Service Categories (Architecture §6.9: Exactly 10)
    $categoryCount = ServiceCategory::query()->count();
    expect($categoryCount)->toBe(10);

    // 2. Verify Document Types (Architecture §6.9: ~25 extracted types)
    $docTypeCount = DocumentType::query()->count();
    expect($docTypeCount)->toBe(25);

    // 3. Verify Services (Architecture §6.9: >= 49, actual: 73)
    $serviceCount = Service::query()->count();
    expect($serviceCount)->toBe(73);

    // Verify required docs relations are seeded
    $requiredDocsCount = ServiceRequiredDoc::query()->count();
    expect($requiredDocsCount)->toBeGreaterThan(50);

    // 4. Verify Offices (Architecture §6.9: ~15-25 extracted offices)
    $officeCount = Office::query()->count();
    expect($officeCount)->toBe(25);

    // Verify office relations
    $coverageCount = OfficeServiceCoverage::query()->count();
    $specialtyCount = OfficeSpecialty::query()->count();
    $medalCount = OfficeMedal::query()->count();

    expect($coverageCount)->toBeGreaterThan(100)
        ->and($specialtyCount)->toBeGreaterThan(30)
        ->and($medalCount)->toBeGreaterThan(30);
});

it('verifies reference seeders are strictly idempotent upon consecutive execution (§6.9, TASK-040-T)', function (): void {
    // First execution
    $this->seed([
        ProvinceSeeder::class,
        ServiceCategorySeeder::class,
        DocumentTypeSeeder::class,
        ServiceSeeder::class,
        OfficeSeeder::class,
    ]);

    $initialCategories = ServiceCategory::query()->count();
    $initialDocTypes = DocumentType::query()->count();
    $initialServices = Service::query()->count();
    $initialRequiredDocs = ServiceRequiredDoc::query()->count();
    $initialOffices = Office::query()->count();
    $initialCoverages = OfficeServiceCoverage::query()->count();
    $initialSpecialties = OfficeSpecialty::query()->count();
    $initialMedals = OfficeMedal::query()->count();

    // Second execution (re-run)
    $this->seed([
        ProvinceSeeder::class,
        ServiceCategorySeeder::class,
        DocumentTypeSeeder::class,
        ServiceSeeder::class,
        OfficeSeeder::class,
    ]);

    expect(ServiceCategory::query()->count())->toBe($initialCategories)
        ->and(DocumentType::query()->count())->toBe($initialDocTypes)
        ->and(Service::query()->count())->toBe($initialServices)
        ->and(ServiceRequiredDoc::query()->count())->toBe($initialRequiredDocs)
        ->and(Office::query()->count())->toBe($initialOffices)
        ->and(OfficeServiceCoverage::query()->count())->toBe($initialCoverages)
        ->and(OfficeSpecialty::query()->count())->toBe($initialSpecialties)
        ->and(OfficeMedal::query()->count())->toBe($initialMedals);
});

it('aborts demo seeder execution when application is in production environment with HTTP 403 (§6.9, TASK-040-T)', function (): void {
    $this->app->detectEnvironment(fn () => 'production');
    expect(app()->isProduction())->toBeTrue();

    try {
        (new DemoSeeder)->run();
        test()->fail('Expected HttpException 403 was not thrown');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403)
            ->and($e->getMessage())->toContain('Demo seeders cannot be executed in production environment');
    }
});

it('seeds demo citizens successfully in non-production environments (§6.9, TASK-040-T)', function (): void {
    $this->seed([
        RoleSeeder::class,
        DemoSeeder::class,
    ]);

    $demoCitizens = Citizen::query()->get();
    expect($demoCitizens)->toHaveCount(3);

    $encryptor = app(EnvelopeEncryptor::class);
    $primaryCitizen = Citizen::query()->where('national_id_hash', $encryptor->hashIndex('0019845621'))->first();
    expect($primaryCitizen)->not->toBeNull()
        ->and($primaryCitizen?->full_name)->toBe('محمدرضا رضایی دهکردی')
        ->and($primaryCitizen?->mobile)->toBe('09123456789')
        ->and($primaryCitizen?->national_id)->toBe('0019845621')
        ->and($primaryCitizen?->tier->value)->toBe('silver');
});

it('runs full DatabaseSeeder orchestrator end-to-end in non-production environment', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(ServiceCategory::query()->count())->toBe(10)
        ->and(Service::query()->count())->toBe(73)
        ->and(Office::query()->count())->toBe(25)
        ->and(Citizen::query()->count())->toBe(3);
});

it('verifies office coordinates conversion to PostGIS geography or fallback representation', function (): void {
    $this->seed([
        ProvinceSeeder::class,
        ServiceCategorySeeder::class,
        OfficeSeeder::class,
    ]);

    $driver = (string) config('database.connections.'.config('database.default').'.driver');

    /** @var Office $office */
    $office = Office::query()->where('code', '1402')->firstOrFail();

    if ($driver === 'pgsql') {
        // Query ST_AsText(location) to verify valid point geometry in Tehran
        $point = DB::selectOne('
            SELECT ST_AsText(location::geometry) as geom_text,
                   ST_X(location::geometry) as lng,
                   ST_Y(location::geometry) as lat
            FROM offices
            WHERE id = ?
        ', [$office->id]);

        expect($point)->not->toBeNull()
            ->and($point->geom_text)->toContain('POINT(51.4083 35.7592)')
            ->and(round((float) $point->lng, 4))->toBe(51.4083)
            ->and(round((float) $point->lat, 4))->toBe(35.7592);
    } else {
        expect($office->location)->toBe('35.7592,51.4083');
    }
});
