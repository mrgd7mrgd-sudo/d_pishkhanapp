<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\CaseWorkflow\Domain\Enums\ReturnReasonCode;
use App\Modules\CaseWorkflow\Domain\Models\ReturnReason;
use Illuminate\Database\Seeder;

final class ReturnReasonSeeder extends Seeder
{
    /**
     * Run the database seeds (§6.1, §6.3, TASK-051).
     * Reference seeder for the 10 return reasons. Idempotent.
     */
    public function run(): void
    {
        foreach (ReturnReasonCode::cases() as $code) {
            ReturnReason::query()->updateOrCreate(
                ['code' => $code->value],
                [
                    'title' => $code->label(),
                    'default_message' => $code->defaultMessage(),
                    'is_active' => true,
                ]
            );
        }
    }
}
