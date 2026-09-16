<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Application\Actions;

use App\Modules\Consultation\Domain\Enums\AdvisorApplicationStatus;
use App\Modules\Consultation\Domain\Models\Advisor;
use App\Modules\Identity\Domain\Enums\RoleName;
use App\Modules\Identity\Domain\Models\Operator;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ApproveAdvisorAction
{
    /**
     * Approve advisor application by a system admin.
     * Architecture §5.3, §7.3: Invariant: activation only upon admin approval & valid license; grants advisor role.
     */
    public function execute(Operator $admin, string $advisorId): Advisor
    {
        if (! $admin->hasRole('system_admin')) {
            throw new InvalidArgumentException('فقط مدیر سامانه مجاز به تأیید مشاوران است.');
        }

        /** @var Advisor|null $advisor */
        $advisor = Advisor::query()->with('citizen')->find($advisorId);
        if ($advisor === null) {
            throw new InvalidArgumentException('مشاور یافت نشد.');
        }

        if ($advisor->application_status === AdvisorApplicationStatus::Approved) {
            return $advisor;
        }

        if (empty($advisor->license_number)) {
            throw new InvalidArgumentException('شماره پروانه مشاور نامعتبر است.');
        }

        return DB::transaction(function () use ($admin, $advisor): Advisor {
            $advisor->update([
                'application_status' => AdvisorApplicationStatus::Approved,
                'is_verified' => true,
                'rejection_reason' => null,
            ]);

            // Grant advisor role to the citizen user (§5.3, §7.3)
            $citizen = $advisor->citizen;
            if ($citizen !== null && ! $citizen->hasRole(RoleName::ADVISOR->value)) {
                $citizen->assignRole(RoleName::ADVISOR->value);
            }

            AuditLogger::record(
                action: AuditableAction::ADVISOR_APPROVED,
                subject: $advisor,
                changes: [
                    'advisor_id' => $advisor->id,
                    'citizen_id' => $advisor->citizen_id,
                    'status' => AdvisorApplicationStatus::Approved->value,
                    'is_verified' => true,
                    'approved_by_admin_id' => $admin->id,
                ],
                actorType: Operator::class,
                actorId: $admin->id
            );

            return $advisor->fresh(['specialties', 'citizen']);
        });
    }
}
