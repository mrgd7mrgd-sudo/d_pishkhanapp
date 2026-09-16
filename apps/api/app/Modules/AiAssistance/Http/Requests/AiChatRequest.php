<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AiChatRequest extends FormRequest
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
            'conversation_id' => ['nullable', 'string', 'max:64'],
            'message' => ['required', 'string', 'min:1', 'max:2000'],
            'context' => ['nullable', 'array'],
            'context.current_route' => ['nullable', 'string', 'max:100'],
            'context.case_id' => ['nullable', 'string', 'max:64'],
            'context.location' => ['nullable', 'array'],
            'context.location.lat' => ['nullable', 'numeric'],
            'context.location.lng' => ['nullable', 'numeric'],
        ];
    }
}
