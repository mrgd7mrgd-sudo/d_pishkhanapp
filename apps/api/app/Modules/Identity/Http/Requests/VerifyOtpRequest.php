<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyOtpRequest extends FormRequest
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
            'challenge_id' => [
                'required',
                'string',
                'uuid',
            ],
            'code' => [
                'required',
                'string',
                'regex:/^[0-9]{5}$/',
            ],
            'device_name' => [
                'nullable',
                'string',
                'max:150',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'challenge_id.required' => 'شناسه چالش الزامی است.',
            'challenge_id.uuid' => 'فرمت شناسه چالش معتبر نیست.',
            'code.required' => 'کد یک‌بارمصرف الزامی است.',
            'code.regex' => 'کد یک‌بارمصرف باید ۵ رقم باشد.',
        ];
    }
}
