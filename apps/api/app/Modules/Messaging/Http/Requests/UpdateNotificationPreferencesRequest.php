<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Http\Requests;

use App\Modules\Messaging\Domain\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.notification_type' => ['required', 'string', Rule::in(NotificationType::values())],
            'preferences.*.sms_enabled' => ['required', 'boolean'],
            'preferences.*.push_enabled' => ['required', 'boolean'],
        ];
    }
}
