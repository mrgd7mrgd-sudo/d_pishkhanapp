<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Requests;

use App\Modules\OfficeNetwork\Domain\Enums\AppointmentCompletion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SetCompletionRequest extends FormRequest
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
            'completion' => ['required', 'string', Rule::enum(AppointmentCompletion::class)],
            'completion_reason' => [
                'required_if:completion,'.AppointmentCompletion::NotCompleted->value,
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
}
