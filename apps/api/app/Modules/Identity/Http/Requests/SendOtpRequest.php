<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Rules\ValidIranianNationalId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class SendOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mobile' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
            ],
            'national_id' => [
                'nullable',
                'string',
                new ValidIranianNationalId,
            ],
            'purpose' => [
                'required',
                'string',
                new Enum(OtpPurpose::class),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mobile.required' => 'شماره موبایل الزامی است.',
            'mobile.regex' => 'شماره موبایل وارد شده معتبر نیست. الگو: ۰۹xxxxxxxxx',
            'purpose.required' => 'هدف از ارسال کد مشخص نشده است.',
        ];
    }
}
