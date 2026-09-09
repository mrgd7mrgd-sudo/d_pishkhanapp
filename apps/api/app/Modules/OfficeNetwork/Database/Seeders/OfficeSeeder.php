<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Database\Seeders;

use App\Modules\OfficeNetwork\Domain\Enums\OfficeMembershipStatus;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeMedal;
use App\Modules\OfficeNetwork\Domain\Models\OfficeServiceCoverage;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSpecialty;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class OfficeSeeder extends Seeder
{
    /**
     * Run the database seeds (§6.1, §6.4, §6.9, TASK-040).
     * Reference seeder for ~25 offices, relations, and geography location. Idempotent.
     */
    public function run(): void
    {
        $jsonPath = App::databasePath('seeders/data/offices.json');
        if (! File::exists($jsonPath)) {
            return;
        }

        $content = File::get($jsonPath);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        /** @var list<array{code: string, name: string, manager_name?: string, membership_status?: string, is_online?: bool, rating?: float, review_count?: int, address?: string|null, province_code?: string|null, city?: string|null, latitude?: float|null, longitude?: float|null, phone?: string|null, working_hours?: array<string, mixed>|null, active_counters?: int, current_waiting_queue?: int, sla_score?: float, specialties?: list<string>, medals?: list<string>, supported_category_ids?: list<string>}> $offices */
        $offices = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $driver = DB::connection()->getDriverName();

        foreach ($offices as $item) {
            $this->seedSingleOffice($item, $driver);
        }
    }

    /**
     * @param  array{code: string, name: string, manager_name?: string, membership_status?: string, is_online?: bool, rating?: float, review_count?: int, address?: string|null, province_code?: string|null, city?: string|null, latitude?: float|null, longitude?: float|null, phone?: string|null, working_hours?: array<string, mixed>|null, active_counters?: int, current_waiting_queue?: int, sla_score?: float, specialties?: list<string>, medals?: list<string>, supported_category_ids?: list<string>}  $item
     */
    private function seedSingleOffice(array $item, string $driver): void
    {
        $cityId = $this->resolveCityId($item['province_code'] ?? null, $item['city'] ?? null);
        $location = $this->resolveLocation($item['latitude'] ?? null, $item['longitude'] ?? null, $driver);

        $status = OfficeMembershipStatus::tryFrom($item['membership_status'] ?? '')
            ?? OfficeMembershipStatus::UNREGISTERED;

        /** @var Office $office */
        $office = Office::query()->updateOrCreate(
            ['code' => $item['code']],
            [
                'name' => $item['name'],
                'manager_name' => $item['manager_name'] ?? '',
                'membership_status' => $status,
                'is_online' => $item['is_online'] ?? true,
                'rating' => $item['rating'] ?? 0.00,
                'review_count' => $item['review_count'] ?? 0,
                'address' => $item['address'] ?? null,
                'city_id' => $cityId,
                'province_code' => $item['province_code'] ?? null,
                'location' => $location,
                'phone' => $item['phone'] ?? null,
                'working_hours' => $item['working_hours'] ?? null,
                'active_counters' => $item['active_counters'] ?? 1,
                'current_waiting_queue' => $item['current_waiting_queue'] ?? 0,
                'sla_score' => $item['sla_score'] ?? 100.00,
            ]
        );

        $this->seedServiceCoverages($office, $item['supported_category_ids'] ?? []);
        $this->seedSpecialties($office, $item['specialties'] ?? []);
        $this->seedMedals($office, $item['medals'] ?? []);
    }

    private function resolveCityId(?string $provinceCode, ?string $cityName): ?string
    {
        if (empty($provinceCode) || empty($cityName)) {
            return null;
        }

        $id = DB::table('cities')
            ->where('province_code', $provinceCode)
            ->where('name', $cityName)
            ->value('id');

        return is_string($id) ? $id : null;
    }

    private function resolveLocation(?float $lat, ?float $lng, string $driver): string|Expression|null
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        if ($driver === 'pgsql') {
            return DB::raw("ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)::geography");
        }

        return "{$lat},{$lng}";
    }

    /**
     * @param  list<string>  $categoryIds
     */
    private function seedServiceCoverages(Office $office, array $categoryIds): void
    {
        $existingCategories = DB::table('service_categories')->pluck('id')->all();

        foreach ($categoryIds as $categoryId) {
            if (! in_array($categoryId, $existingCategories, true)) {
                continue;
            }

            OfficeServiceCoverage::query()->updateOrCreate(
                [
                    'office_id' => $office->id,
                    'category_id' => $categoryId,
                    'service_id' => null,
                ],
                [
                    'is_active' => true,
                    'daily_capacity' => 50,
                ]
            );
        }
    }

    /**
     * @param  list<string>  $specialties
     */
    private function seedSpecialties(Office $office, array $specialties): void
    {
        foreach ($specialties as $title) {
            OfficeSpecialty::query()->updateOrCreate(
                [
                    'office_id' => $office->id,
                    'title' => $title,
                ],
                [
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @param  list<string>  $medals
     */
    private function seedMedals(Office $office, array $medals): void
    {
        foreach ($medals as $title) {
            OfficeMedal::query()->updateOrCreate(
                [
                    'office_id' => $office->id,
                    'title' => $title,
                ],
                [
                    'icon' => 'medal-gold',
                    'earned_at' => Carbon::now()->subMonths(3),
                ]
            );
        }
    }
}
