<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitOfficeReviewRequest extends FormRequest
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
            'case_id' => ['required', 'uuid', 'exists:case_requests,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:3', 'max:2000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
        ];
    }
}
