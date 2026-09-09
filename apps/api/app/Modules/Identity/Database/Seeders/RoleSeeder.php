<?php

declare(strict_types=1);

namespace App\Modules\Identity\Database\Seeders;

use App\Modules\Identity\Domain\Enums\RoleName;
use App\Modules\Identity\Domain\Enums\SystemPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RoleSeeder extends Seeder
{
    private const GUARD = 'web';

    public function run(): void
    {
        $this->seedPermissions();
        $this->seedCitizenRoles();
        $this->seedAdvisorRole();
        $this->seedOfficeRoles();
        $this->seedAdminAndAuditorRoles();
        $this->clearPermissionCache();
    }

    private function seedPermissions(): void
    {
        foreach (SystemPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, self::GUARD);
        }
    }

    private function seedCitizenRoles(): void
    {
        $citizen = Role::findOrCreate(RoleName::CITIZEN->value, self::GUARD);
        $citizen->syncPermissions([
            SystemPermission::SERVICES_VIEW->value,
            SystemPermission::CASES_CREATE->value,
            SystemPermission::CASES_VIEW->value,
            SystemPermission::DOCUMENTS_DOWNLOAD->value,
            SystemPermission::DELEGATION_CREATE->value,
        ]);

        $delegate = Role::findOrCreate(RoleName::CITIZEN_DELEGATE->value, self::GUARD);
        $delegate->syncPermissions([
            SystemPermission::SERVICES_VIEW->value,
            SystemPermission::CASES_CREATE->value,
            SystemPermission::CASES_VIEW->value,
            SystemPermission::DOCUMENTS_DOWNLOAD->value,
        ]);
    }

    private function seedAdvisorRole(): void
    {
        $advisor = Role::findOrCreate(RoleName::ADVISOR->value, self::GUARD);
        $advisor->syncPermissions([
            SystemPermission::SERVICES_VIEW->value,
            SystemPermission::CONSULTATIONS_CONDUCT->value,
        ]);
    }

    private function seedOfficeRoles(): void
    {
        $operator = Role::findOrCreate(RoleName::OFFICE_OPERATOR->value, self::GUARD);
        $operator->syncPermissions([
            SystemPermission::SERVICES_VIEW->value,
            SystemPermission::CASES_VIEW->value,
            SystemPermission::DOCUMENTS_DOWNLOAD->value,
            SystemPermission::DISPATCH_ACCEPT->value,
            SystemPermission::CASES_RETURN->value,
            SystemPermission::CASES_COMPLETE->value,
            SystemPermission::DELIVERIES_CREATE->value,
            SystemPermission::DELIVERIES_CONFIRM_OTP->value,
        ]);

        $manager = Role::findOrCreate(RoleName::OFFICE_MANAGER->value, self::GUARD);
        $manager->syncPermissions([
            SystemPermission::SERVICES_VIEW->value,
            SystemPermission::CASES_VIEW->value,
            SystemPermission::DOCUMENTS_DOWNLOAD->value,
            SystemPermission::DISPATCH_ACCEPT->value,
            SystemPermission::CASES_RETURN->value,
            SystemPermission::CASES_REJECT->value,
            SystemPermission::CASES_COMPLETE->value,
            SystemPermission::DELIVERIES_CREATE->value,
            SystemPermission::DELIVERIES_CONFIRM_OTP->value,
            SystemPermission::FINANCES_VIEW->value,
            SystemPermission::REVIEWS_REPLY->value,
            SystemPermission::OFFICE_PROFILE_UPDATE->value,
            SystemPermission::OFFICE_OPERATORS_MANAGE->value,
            SystemPermission::AUDIT_LOGS_VIEW->value,
        ]);
    }

    private function seedAdminAndAuditorRoles(): void
    {
        $admin = Role::findOrCreate(RoleName::SYSTEM_ADMIN->value, self::GUARD);
        $admin->syncPermissions([
            SystemPermission::SERVICES_VIEW->value,
            SystemPermission::CASES_VIEW->value,
            SystemPermission::DOCUMENTS_DOWNLOAD->value,
            SystemPermission::CASES_REJECT->value,
            SystemPermission::FINANCES_VIEW->value,
            SystemPermission::REVIEWS_REPLY->value,
            SystemPermission::OFFICE_PROFILE_UPDATE->value,
            SystemPermission::OFFICE_OPERATORS_MANAGE->value,
            SystemPermission::OFFICE_REGISTER_APPROVE->value,
            SystemPermission::ADVISOR_REGISTER_APPROVE->value,
            SystemPermission::SERVICE_CATALOG_MANAGE->value,
            SystemPermission::AUDIT_LOGS_VIEW->value,
        ]);

        $auditor = Role::findOrCreate(RoleName::AUDITOR->value, self::GUARD);
        $auditor->syncPermissions([
            SystemPermission::SERVICES_VIEW->value,
            SystemPermission::CASES_VIEW->value,
            SystemPermission::FINANCES_VIEW->value,
            SystemPermission::AUDIT_LOGS_VIEW->value,
        ]);
    }

    private function clearPermissionCache(): void
    {
        App::make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
