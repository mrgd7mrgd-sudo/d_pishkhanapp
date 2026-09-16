<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Http\Requests;

use App\Modules\Consultation\Domain\Enums\ConsultationCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class SubmitAdvisorApplicationRequest extends FormRequest
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
            'display_name' => ['required', 'string', 'min:3', 'max:120'],
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'category' => ['required', new Enum(ConsultationCategory::class)],
            'license_number' => ['required', 'string', 'max:64'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:60'],
            'price_text_chat_rials' => ['required', 'integer', 'min:0'],
            'price_phone_per_minute_rials' => ['required', 'integer', 'min:0'],
            'price_deep_review_rials' => ['required', 'integer', 'min:0'],
            'bio' => ['required', 'string', 'max:2000'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['string', 'max:100'],
            'credentials_badge' => ['nullable', 'string', 'max:120'],
        ];
    }
}
