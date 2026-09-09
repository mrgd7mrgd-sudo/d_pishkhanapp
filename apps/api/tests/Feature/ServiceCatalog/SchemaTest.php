<?php

declare(strict_types=1);

namespace Tests\Feature\ServiceCatalog;

use App\Modules\ServiceCatalog\Domain\Enums\ServiceTag;
use App\Modules\ServiceCatalog\Domain\Models\DocumentType;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use App\Modules\ServiceCatalog\Domain\Models\ServiceRequiredDoc;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('ensures service_categories has expected columns and explicitly excludes service_count', function (): void {
    expect(Schema::hasTable('service_categories'))->toBeTrue();

    // Mandatory columns
    expect(Schema::hasColumn('service_categories', 'id'))->toBeTrue()
        ->and(Schema::hasColumn('service_categories', 'title'))->toBeTrue()
        ->and(Schema::hasColumn('service_categories', 'short_title'))->toBeTrue()
        ->and(Schema::hasColumn('service_categories', 'icon_name'))->toBeTrue()
        ->and(Schema::hasColumn('service_categories', 'color'))->toBeTrue()
        ->and(Schema::hasColumn('service_categories', 'badge'))->toBeTrue()
        ->and(Schema::hasColumn('service_categories', 'description'))->toBeTrue()
        ->and(Schema::hasColumn('service_categories', 'sort_order'))->toBeTrue()
        ->and(Schema::hasColumn('service_categories', 'is_active'))->toBeTrue()
        ->and(Schema::hasColumn('service_categories', 'created_at'))->toBeTrue()
        ->and(Schema::hasColumn('service_categories', 'deleted_at'))->toBeTrue();

    // ARCHITECTURE §6.2 STRICT RULE: service_count is a derived attribute and MUST NOT be stored!
    expect(Schema::hasColumn('service_categories', 'service_count'))->toBeFalse();
});

it('ensures services table has all required columns including unsigned integer fee_rials', function (): void {
    expect(Schema::hasTable('services'))->toBeTrue();

    expect(Schema::hasColumn('services', 'id'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'category_id'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'slug'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'title'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'description'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'tags'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'requirements'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'estimated_days_min'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'estimated_days_max'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'fee_rials'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'office_share_percent'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'department'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'is_popular'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'is_new'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'is_active'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'search_vector'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'created_at'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'deleted_at'))->toBeTrue();
});

it('verifies fee_rials stores integer Rials and casts properly (D-06)', function (): void {
    $category = ServiceCategory::query()->create([
        'id' => 'civil-reg',
        'title' => 'ثبت احوال',
    ]);

    $service = Service::query()->create([
        'category_id' => $category->id,
        'slug' => 'national-id-card-issuance',
        'title' => 'صدور کارت هوشمند ملی',
        'description' => 'درخواست صدور کارت هوشمند ملی برای متقاضیان',
        'tags' => [ServiceTag::ONLINE->value, ServiceTag::SEMI_ONLINE->value],
        'fee_rials' => 15000000,
        'office_share_percent' => 70.00,
    ]);

    $fresh = Service::query()->findOrFail($service->id);
    expect($fresh->fee_rials)->toBeInt()
        ->and($fresh->fee_rials)->toBe(15000000)
        ->and($fresh->office_share_percent)->toBe(70.0)
        ->and($fresh->tags)->toBe(['online', 'semi-online']);
});

it('enforces foreign key constraints between services and categories', function (): void {
    expect(function (): void {
        Service::query()->create([
            'category_id' => 'non-existent-category',
            'slug' => 'invalid-service',
            'title' => 'خدمت نامعتبر',
            'description' => 'توضیحات تست',
            'tags' => ['online'],
            'fee_rials' => 500000,
        ]);
    })->toThrow(QueryException::class);
});

it('enforces foreign key constraints between service_required_docs and document_types', function (): void {
    $category = ServiceCategory::query()->create([
        'id' => 'passport-services',
        'title' => 'گذرنامه',
    ]);

    $service = Service::query()->create([
        'category_id' => $category->id,
        'slug' => 'passport-renewal',
        'title' => 'تعویض گذرنامه',
        'description' => 'تعویض گذرنامه منقضی شده',
        'tags' => ['in-person'],
        'fee_rials' => 20000000,
    ]);

    expect(function () use ($service): void {
        ServiceRequiredDoc::query()->create([
            'service_id' => $service->id,
            'document_type_code' => 'NON_EXISTENT_DOC_CODE',
            'is_mandatory' => true,
        ]);
    })->toThrow(QueryException::class);
});

it('enforces unique constraint on service_id and document_type_code', function (): void {
    $category = ServiceCategory::query()->create([
        'id' => 'driving-license',
        'title' => 'گواهینامه رانندگی',
    ]);

    $service = Service::query()->create([
        'category_id' => $category->id,
        'slug' => 'license-renewal',
        'title' => 'تمدید گواهینامه',
        'description' => 'تمدید گواهینامه پایه سوم',
        'tags' => ['semi-online'],
        'fee_rials' => 10000000,
    ]);

    $docType = DocumentType::query()->create([
        'code' => 'DOC_NATIONAL_CARD',
        'title' => 'کارت ملی',
        'accepted_mimes' => ['image/jpeg', 'application/pdf'],
        'requires_original' => true,
    ]);

    ServiceRequiredDoc::query()->create([
        'service_id' => $service->id,
        'document_type_code' => $docType->code,
        'is_mandatory' => true,
    ]);

    expect(function () use ($service, $docType): void {
        ServiceRequiredDoc::query()->create([
            'service_id' => $service->id,
            'document_type_code' => $docType->code,
            'is_mandatory' => false,
        ]);
    })->toThrow(QueryException::class);
});

it('restricts deletion of document_type when referenced in service_required_docs', function (): void {
    $category = ServiceCategory::query()->create([
        'id' => 'tax-services',
        'title' => 'امور مالیاتی',
    ]);

    $service = Service::query()->create([
        'category_id' => $category->id,
        'slug' => 'tax-statement',
        'title' => 'اظهارنامه مالیاتی',
        'description' => 'ثبت اظهارنامه مالیاتی مشاغل',
        'tags' => ['online'],
        'fee_rials' => 8000000,
    ]);

    $docType = DocumentType::query()->create([
        'code' => 'DOC_TAX_CLEARANCE',
        'title' => 'مفاصا حساب مالیاتی',
        'accepted_mimes' => ['application/pdf'],
    ]);

    ServiceRequiredDoc::query()->create([
        'service_id' => $service->id,
        'document_type_code' => $docType->code,
    ]);

    expect(function () use ($docType): void {
        $docType->delete();
    })->toThrow(QueryException::class);
});

it('verifies all model relationships work properly', function (): void {
    $category = ServiceCategory::query()->create([
        'id' => 'post-services',
        'title' => 'خدمات پستی',
    ]);

    $service = Service::query()->create([
        'category_id' => $category->id,
        'slug' => 'parcel-tracking',
        'title' => 'رهگیری مرسوله',
        'description' => 'رهگیری آنلاین مرسولات پستی پیشتاز',
        'tags' => [ServiceTag::ONLINE->value],
        'fee_rials' => 0,
    ]);

    $docType = DocumentType::query()->create([
        'code' => 'DOC_POSTAL_BARCODE',
        'title' => 'بارکد مرسوله',
        'accepted_mimes' => ['image/png', 'image/jpeg'],
    ]);

    $requiredDoc = ServiceRequiredDoc::query()->create([
        'service_id' => $service->id,
        'document_type_code' => $docType->code,
        'is_mandatory' => true,
        'sort_order' => 1,
    ]);

    // Category -> Services
    expect($category->services)->toHaveCount(1)
        ->and($category->services->first()?->id)->toBe($service->id);

    // Service -> Category
    expect($service->category->id)->toBe($category->id);

    // Service -> RequiredDocs
    expect($service->requiredDocs)->toHaveCount(1)
        ->and($service->requiredDocs->first()?->id)->toBe($requiredDoc->id);

    // Service -> DocumentTypes (BelongsToMany)
    expect($service->documentTypes)->toHaveCount(1)
        ->and($service->documentTypes->first()?->code)->toBe($docType->code)
        ->and($service->documentTypes->first()?->pivot?->is_mandatory)->toBe(1);

    // DocumentType -> RequiredDocs
    expect($docType->requiredDocs)->toHaveCount(1)
        ->and($docType->requiredDocs->first()?->id)->toBe($requiredDoc->id);

    // DocumentType -> Services (BelongsToMany)
    expect($docType->services)->toHaveCount(1)
        ->and($docType->services->first()?->id)->toBe($service->id);
});
