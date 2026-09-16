<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Requests;

use App\Modules\OfficeNetwork\Domain\Enums\AppointmentAttendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SetAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attendance' => ['required', 'string', Rule::enum(AppointmentAttendance::class)],
            'counter_number' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
