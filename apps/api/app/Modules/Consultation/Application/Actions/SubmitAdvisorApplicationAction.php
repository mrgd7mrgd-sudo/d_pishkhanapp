<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Application\Actions;

use App\Modules\Consultation\Domain\Enums\AdvisorApplicationStatus;
use App\Modules\Consultation\Domain\Enums\ConsultationCategory;
use App\Modules\Consultation\Domain\Models\Advisor;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SubmitAdvisorApplicationAction
{
    /**
     * Submit an advisor application for an authenticated citizen.
     *
     * @param  array<int, string>  $specialties
     */
    public function execute(
        Citizen $citizen,
        string $displayName,
        string $title,
        ConsultationCategory $category,
        string $licenseNumber,
        int $experienceYears,
        int $priceTextChatRials,
        int $pricePhonePerMinuteRials,
        int $priceDeepReviewRials,
        string $bio,
        array $specialties = [],
        ?string $credentialsBadge = null
    ): Advisor {
        // Enforce uniqueness: citizen can only have one active/pending application
        $existing = Advisor::query()->where('citizen_id', $citizen->id)->first();
        if ($existing !== null && $existing->application_status !== AdvisorApplicationStatus::Rejected) {
            throw new InvalidArgumentException('شما قبلاً درخواست مشاوره ثبت کرده‌اید.');
        }

        // License number must be unique across all advisors
        $duplicateLicense = Advisor::query()
            ->where('license_number', $licenseNumber)
            ->when($existing !== null, fn ($q) => $q->where('id', '!=', $existing->id))
            ->exists();

        if ($duplicateLicense) {
            throw new InvalidArgumentException('شماره پروانه وارد شده قبلاً در سامانه ثبت شده است.');
        }

        if ($experienceYears < 0) {
            throw new InvalidArgumentException('سابقه کار نمی‌تواند منفی باشد.');
        }

        if ($priceTextChatRials < 0 || $pricePhonePerMinuteRials < 0 || $priceDeepReviewRials < 0) {
            throw new InvalidArgumentException('تعرفه‌ها نمی‌توانند منفی باشند.');
        }

        return DB::transaction(function () use (
            $citizen,
            $displayName,
            $title,
            $category,
            $licenseNumber,
            $experienceYears,
            $priceTextChatRials,
            $pricePhonePerMinuteRials,
            $priceDeepReviewRials,
            $bio,
            $specialties,
            $credentialsBadge
        ): Advisor {
            /** @var Advisor $advisor */
            $advisor = Advisor::query()->updateOrCreate(
                ['citizen_id' => $citizen->id],
                [
                    'display_name' => $displayName,
                    'title' => $title,
                    'category' => $category,
                    'license_number' => $licenseNumber,
                    'experience_years' => $experienceYears,
                    'price_text_chat_rials' => $priceTextChatRials,
                    'price_phone_per_minute_rials' => $pricePhonePerMinuteRials,
                    'price_deep_review_rials' => $priceDeepReviewRials,
                    'bio' => $bio,
                    'credentials_badge' => $credentialsBadge,
                    'application_status' => AdvisorApplicationStatus::Pending,
                    'is_verified' => false,
                    'is_online' => false,
                    'rejection_reason' => null,
                ]
            );

            // Sync specialties
            $advisor->specialties()->delete();
            foreach ($specialties as $specialtyName) {
                if (trim($specialtyName) !== '') {
                    $advisor->specialties()->create([
                        'id' => (string) Str::uuid(),
                        'specialty_name' => trim($specialtyName),
                    ]);
                }
            }

            AuditLogger::record(
                action: 'advisor.application_submitted',
                subject: $advisor,
                changes: [
                    'advisor_id' => $advisor->id,
                    'citizen_id' => $citizen->id,
                    'category' => $category->value,
                    'license_number' => $licenseNumber,
                    'status' => AdvisorApplicationStatus::Pending->value,
                ],
                actorType: Citizen::class,
                actorId: $citizen->id
            );

            return $advisor->fresh(['specialties']);
        });
    }
}
