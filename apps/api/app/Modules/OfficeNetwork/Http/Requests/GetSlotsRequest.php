<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GetSlotsRequest extends FormRequest
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
            'date' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }
}
