<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\RoleName;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Shared\Crypto\EnvelopeEncryptor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

final class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds (§6.9, TASK-040).
     * Protected demo seeder. Must abort in production environment.
     */
    public function run(): void
    {
        abort_if(app()->isProduction(), 403, 'Demo seeders cannot be executed in production environment');

        $this->seedCitizens();
    }

    private function seedCitizens(): void
    {
        $jsonPath = database_path('seeders/data/demo_citizens.json');
        if (! File::exists($jsonPath) || ! Schema::hasTable('citizens')) {
            return;
        }

        $content = File::get($jsonPath);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        /** @var list<array{national_id: string, mobile: string, full_name: string, father_name?: string|null, birth_date?: string|null, postal_code?: string|null, address?: string|null, tier?: string|null, sana_verified?: bool, digital_signature_active?: bool, credit_score?: int}> $citizens */
        $citizens = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $encryptor = app(EnvelopeEncryptor::class);

        foreach ($citizens as $cit) {
            $mobileHash = $encryptor->hashIndex($cit['mobile']);

            /** @var Citizen $citizen */
            $citizen = Citizen::query()->where('mobile_hash', $mobileHash)->first() ?? new Citizen;

            $citizen->national_id = $cit['national_id'];
            $citizen->mobile = $cit['mobile'];
            $citizen->full_name = $cit['full_name'];
            $citizen->father_name = $cit['father_name'] ?? null;
            $citizen->birth_date = $cit['birth_date'] ?? null;
            $citizen->postal_code = $cit['postal_code'] ?? null;
            $citizen->address = $cit['address'] ?? null;
            $citizen->tier = CitizenTier::tryFrom($cit['tier'] ?? 'bronze') ?? CitizenTier::BRONZE;
            $citizen->sana_verified = $cit['sana_verified'] ?? false;
            $citizen->digital_signature_active = $cit['digital_signature_active'] ?? false;
            $citizen->credit_score = $cit['credit_score'] ?? 500;
            $citizen->save();

            if (class_exists(RoleName::class) && method_exists($citizen, 'assignRole')) {
                $citizen->syncRoles([RoleName::CITIZEN->value]);
            }
        }
    }
}
