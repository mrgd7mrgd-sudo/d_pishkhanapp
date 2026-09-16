<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Controllers;

use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeAnnouncement;
use App\Modules\OfficeNetwork\Domain\Models\OfficeServiceCoverage;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSpecialty;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * DeskOfficeProfileController (§4.4, §7.3, TASK-105).
 * Strictly accessible ONLY by office_manager of the current office.
 */
final class DeskOfficeProfileController
{
    /**
     * Get complete office profile data: info, services/coverage, reception/hours, announcements, operators.
     */
    public function show(Request $request): JsonResponse
    {
        $manager = $this->authorizeManager($request);
        $office = $this->resolveOffice($manager);

        $office->load(['specialties', 'serviceCoverages.category', 'announcements']);

        $operators = Operator::query()
            ->where('office_id', $office->id)
            ->get(['id', 'username', 'full_name', 'counter_number', 'role', 'is_active', 'last_login_at']);

        $categories = ServiceCategory::query()
            ->where('is_active', true)
            ->get(['id', 'title', 'short_title', 'icon_name']);

        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'office' => [
                    'id' => $office->id,
                    'code' => $office->code,
                    'name' => $office->name,
                    'manager_name' => $office->manager_name,
                    'is_online' => $office->is_online,
                    'membership_status' => $office->membership_status->value,
                    'address' => $office->address,
                    'phone' => $office->phone,
                    'working_hours' => $office->working_hours,
                    'active_counters' => $office->active_counters,
                    'current_waiting_queue' => $office->current_waiting_queue,
                    'rating' => $office->rating,
                    'review_count' => $office->review_count,
                    'sla_score' => $office->sla_score,
                ],
                'specialties' => $office->specialties,
                'coverages' => $office->serviceCoverages,
                'announcements' => $office->announcements,
                'operators' => $operators,
                'available_categories' => $categories,
            ],
        ]);
    }

    /**
     * Update basic office info (phone, address, working_hours, active_counters).
     */
    public function updateInfo(Request $request): JsonResponse
    {
        $manager = $this->authorizeManager($request);
        $office = $this->resolveOffice($manager);

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'working_hours' => ['nullable', 'array'],
            'active_counters' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $before = [
            'phone' => $office->phone,
            'address' => $office->address,
            'working_hours' => $office->working_hours,
            'active_counters' => $office->active_counters,
        ];

        $office->update($validated);

        AuditLogger::record(
            action: AuditableAction::OFFICE_PROFILE_UPDATED,
            subject: $office,
            changes: [
                'before' => $before,
                'after' => $validated,
            ],
            context: ['operator_id' => $manager->id]
        );

        return new JsonResponse([
            'status' => 'success',
            'message' => 'اطلاعات عمومی دفتر با موفقیت به‌روزرسانی شد.',
            'data' => $office->fresh(),
        ]);
    }

    /**
     * Update office specialties (tags/titles).
     */
    public function updateSpecialties(Request $request): JsonResponse
    {
        $manager = $this->authorizeManager($request);
        $office = $this->resolveOffice($manager);

        $validated = $request->validate([
            'specialties' => ['required', 'array'],
            'specialties.*' => ['string', 'max:100'],
        ]);

        DB::transaction(function () use ($office, $validated, $manager): void {
            OfficeSpecialty::query()->where('office_id', $office->id)->delete();

            foreach ($validated['specialties'] as $title) {
                if (trim($title) !== '') {
                    OfficeSpecialty::query()->create([
                        'id' => (string) Str::uuid(),
                        'office_id' => $office->id,
                        'title' => trim($title),
                        'is_active' => true,
                    ]);
                }
            }

            AuditLogger::record(
                action: AuditableAction::OFFICE_SPECIALTIES_UPDATED,
                subject: $office,
                changes: ['specialties' => $validated['specialties']],
                context: ['operator_id' => $manager->id]
            );
        });

        return new JsonResponse([
            'status' => 'success',
            'message' => 'تخصص‌های دفتر با موفقیت ذخیره شد.',
            'data' => OfficeSpecialty::query()->where('office_id', $office->id)->get(),
        ]);
    }

    /**
     * Update category service coverages.
     * Invalidates GeohashCache so changes instantly reflect in OfficeFinder.
     */
    public function updateCoverages(Request $request): JsonResponse
    {
        $manager = $this->authorizeManager($request);
        $office = $this->resolveOffice($manager);

        $validated = $request->validate([
            'coverages' => ['required', 'array'],
            'coverages.*.category_id' => ['required', 'string', 'exists:service_categories,id'],
            'coverages.*.is_active' => ['required', 'boolean'],
            'coverages.*.daily_capacity' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($office, $validated, $manager): void {
            foreach ($validated['coverages'] as $cov) {
                OfficeServiceCoverage::query()->updateOrCreate(
                    [
                        'office_id' => $office->id,
                        'category_id' => $cov['category_id'],
                    ],
                    [
                        'is_active' => $cov['is_active'],
                        'daily_capacity' => $cov['daily_capacity'] ?? 100,
                    ]
                );
            }

            AuditLogger::record(
                action: AuditableAction::OFFICE_COVERAGES_UPDATED,
                subject: $office,
                changes: ['coverages' => $validated['coverages']],
                context: ['operator_id' => $manager->id]
            );

            // Flush Geohash nearby cache so OfficeFinder reflects changes immediately
            Cache::flush();
        });

        return new JsonResponse([
            'status' => 'success',
            'message' => 'پوشش خدمات دفتر با موفقیت به‌روزرسانی شد.',
            'data' => OfficeServiceCoverage::query()
                ->where('office_id', $office->id)
                ->with('category')
                ->get(),
        ]);
    }

    /**
     * Create office announcement.
     */
    public function storeAnnouncement(Request $request): JsonResponse
    {
        $manager = $this->authorizeManager($request);
        $office = $this->resolveOffice($manager);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:2000'],
            'priority' => ['nullable', 'string', 'in:normal,important,urgent'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $announcement = OfficeAnnouncement::query()->create([
            'id' => (string) Str::uuid(),
            'office_id' => $office->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'priority' => $validated['priority'] ?? 'normal',
            'starts_at' => $validated['starts_at'] ?? Carbon::now(),
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
        ]);

        AuditLogger::record(
            action: AuditableAction::OFFICE_ANNOUNCEMENT_CREATED,
            subject: $announcement,
            changes: $validated,
            context: ['operator_id' => $manager->id]
        );

        return new JsonResponse([
            'status' => 'success',
            'message' => 'اطلاعیه جدید با موفقیت ثبت شد.',
            'data' => $announcement,
        ], 201);
    }

    /**
     * Delete office announcement.
     */
    public function deleteAnnouncement(Request $request, string $id): JsonResponse
    {
        $manager = $this->authorizeManager($request);
        $office = $this->resolveOffice($manager);

        /** @var OfficeAnnouncement|null $announcement */
        $announcement = OfficeAnnouncement::query()
            ->where('id', $id)
            ->where('office_id', $office->id)
            ->first();

        if ($announcement === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'اطلاعیه یافت نشد.',
            ], 404));
        }

        $announcement->delete();

        AuditLogger::record(
            action: AuditableAction::OFFICE_ANNOUNCEMENT_DELETED,
            subject: $announcement,
            changes: ['deleted' => true],
            context: ['operator_id' => $manager->id]
        );

        return new JsonResponse([
            'status' => 'success',
            'message' => 'اطلاعیه با موفقیت حذف شد.',
        ]);
    }

    /**
     * Create new office operator.
     */
    public function storeOperator(Request $request): JsonResponse
    {
        $manager = $this->authorizeManager($request);
        $office = $this->resolveOffice($manager);

        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:operators,username'],
            'full_name' => ['required', 'string', 'max:150'],
            'counter_number' => ['required', 'integer', 'min:1', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'national_id' => ['nullable', 'string', 'digits:10'],
            'mobile' => ['nullable', 'string', 'regex:/^09[0-9]{9}$/'],
            'role' => ['nullable', 'string', 'in:operator,manager'],
        ]);

        $op = new Operator;
        $op->id = (string) Str::uuid();
        $op->office_id = $office->id;
        $op->username = $validated['username'];
        $op->full_name = $validated['full_name'];
        $op->counter_number = (int) $validated['counter_number'];
        $op->password_hash = Hash::make($validated['password']);
        $op->national_id = $validated['national_id'] ?? '00'.random_int(10000000, 99999999);
        $op->mobile = $validated['mobile'] ?? '0912'.random_int(1000000, 9999999);
        $op->role = ($validated['role'] ?? 'operator') === 'manager' ? OperatorRole::MANAGER : OperatorRole::OPERATOR;
        $op->is_active = true;
        $op->save();

        AuditLogger::record(
            action: AuditableAction::OPERATOR_CREATED,
            subject: $op,
            changes: [
                'username' => $op->username,
                'full_name' => $op->full_name,
                'counter_number' => $op->counter_number,
                'role' => $op->role->value,
            ],
            context: ['manager_id' => $manager->id]
        );

        return new JsonResponse([
            'status' => 'success',
            'message' => 'اپراتور جدید با موفقیت ایجاد شد.',
            'data' => [
                'id' => $op->id,
                'username' => $op->username,
                'full_name' => $op->full_name,
                'counter_number' => $op->counter_number,
                'role' => $op->role->value,
                'is_active' => $op->is_active,
            ],
        ], 201);
    }

    /**
     * Toggle operator active status.
     */
    public function toggleOperator(Request $request, string $id): JsonResponse
    {
        $manager = $this->authorizeManager($request);
        $office = $this->resolveOffice($manager);

        /** @var Operator|null $op */
        $op = Operator::query()
            ->where('id', $id)
            ->where('office_id', $office->id)
            ->first();

        if ($op === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'اپراتور یافت نشد.',
            ], 404));
        }

        $op->is_active = ! $op->is_active;
        $op->save();

        AuditLogger::record(
            action: $op->is_active ? AuditableAction::OPERATOR_UPDATED : AuditableAction::OPERATOR_DISABLED,
            subject: $op,
            changes: ['is_active' => $op->is_active],
            context: ['manager_id' => $manager->id]
        );

        return new JsonResponse([
            'status' => 'success',
            'message' => 'وضعیت اپراتور تغییر یافت.',
            'data' => [
                'id' => $op->id,
                'is_active' => $op->is_active,
            ],
        ]);
    }

    private function authorizeManager(Request $request): Operator
    {
        $operator = $request->user();
        if (! $operator instanceof Operator || $operator->office_id === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'دسترسی غیرمجاز.',
            ], 404));
        }

        $isManager = $operator->hasRole('office_manager') || $operator->role === OperatorRole::MANAGER;
        if (! $isManager) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 403,
                'code' => 'FORBIDDEN_NOT_OFFICE_MANAGER',
                'detail' => 'دسترسی به بخش مدیریت و پروفایل دفتر فقط مختص مدیر دفتر است.',
            ], 403));
        }

        return $operator;
    }

    private function resolveOffice(Operator $manager): Office
    {
        /** @var Office|null $office */
        $office = Office::query()->find($manager->office_id);
        if ($office === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'دفتر مربوطه یافت نشد.',
            ], 404));
        }

        return $office;
    }
}
