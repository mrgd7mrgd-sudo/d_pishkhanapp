<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Requests;

use App\Modules\Payments\Domain\Enums\PaymentGateway;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VerifyTopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'authority' => ['required', 'string', 'max:128'],
            'gateway' => ['nullable', 'string', Rule::enum(PaymentGateway::class)],
            'status' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'authority.required' => 'شناسه پیگیری (authority) درگاه الزامی است.',
            'gateway.enum' => 'درگاه پرداخت انتخاب‌شده معتبر نمی‌باشد.',
        ];
    }
}
