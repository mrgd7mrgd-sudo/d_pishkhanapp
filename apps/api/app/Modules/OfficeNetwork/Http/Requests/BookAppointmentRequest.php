<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'office_id' => ['required', 'uuid', 'exists:offices,id'],
            'service_id' => ['required', 'uuid', 'exists:services,id'],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time_slot' => ['required', 'string', 'max:32'],
            'reminder_type' => ['sometimes', 'string', 'in:sms,push,all'],
            'reminder_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
