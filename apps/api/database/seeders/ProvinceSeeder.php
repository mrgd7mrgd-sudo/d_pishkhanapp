<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Idempotent seeder for 31 provinces and cities (§6.1, §6.5, TASK-018).
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/provinces.json');
        if (! File::exists($jsonPath)) {
            return;
        }

        $content = File::get($jsonPath);
        // Strip UTF-8 BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        /** @var array<int, array{province_code: string, name: string, office_count?: int, cities: array<int, array{name: string, latitude: float, longitude: float}>}> $provinces */
        $provinces = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $driver = config('database.connections.'.config('database.default').'.driver');

        foreach ($provinces as $prov) {
            DB::table('provinces')->updateOrInsert(
                ['province_code' => $prov['province_code']],
                [
                    'name' => $prov['name'],
                    'office_count' => $prov['office_count'] ?? 0,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            foreach ($prov['cities'] as $city) {
                if ($driver === 'pgsql') {
                    $exists = DB::table('cities')
                        ->where('province_code', $prov['province_code'])
                        ->where('name', $city['name'])
                        ->first();

                    $lat = $city['latitude'];
                    $lng = $city['longitude'];

                    if ($exists) {
                        DB::statement('
                            UPDATE cities 
                            SET center = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, updated_at = NOW()
                            WHERE id = ?
                        ', [$lng, $lat, $exists->id]);
                    } else {
                        $id = (string) Str::uuid();
                        DB::statement('
                            INSERT INTO cities (id, province_code, name, center, created_at, updated_at)
                            VALUES (?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, NOW(), NOW())
                        ', [$id, $prov['province_code'], $city['name'], $lng, $lat]);
                    }
                } else {
                    $exists = DB::table('cities')
                        ->where('province_code', $prov['province_code'])
                        ->where('name', $city['name'])
                        ->first();

                    if ($exists) {
                        DB::table('cities')->where('id', $exists->id)->update([
                            'latitude' => $city['latitude'],
                            'longitude' => $city['longitude'],
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('cities')->insert([
                            'id' => (string) Str::uuid(),
                            'province_code' => $prov['province_code'],
                            'name' => $city['name'],
                            'latitude' => $city['latitude'],
                            'longitude' => $city['longitude'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}
