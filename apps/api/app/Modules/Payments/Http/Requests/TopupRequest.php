<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Requests;

use App\Modules\Payments\Domain\Enums\PaymentGateway;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TopupRequest extends FormRequest
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
            'amount_rials' => ['required', 'integer', 'min:10000', 'max:500000000'],
            'gateway' => ['nullable', 'string', Rule::enum(PaymentGateway::class)],
            'return_url' => ['required', 'url', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount_rials.required' => 'مبلغ شارژ الزامی است.',
            'amount_rials.integer' => 'مبلغ شارژ باید به صورت عدد صحیح ریال باشد.',
            'amount_rials.min' => 'حداقل مبلغ شارژ ۱۰٬۰۰۰ ریال (۱٬۰۰۰ تومان) است.',
            'amount_rials.max' => 'حداکثر مبلغ شارژ ۵۰۰٬۰۰۰٬۰۰۰ ریال است.',
            'gateway.enum' => 'درگاه پرداخت انتخاب‌شده معتبر نمی‌باشد.',
            'return_url.required' => 'آدرس بازگشت (return_url) الزامی است.',
            'return_url.url' => 'آدرس بازگشت باید یک URL معتبر باشد.',
        ];
    }
}
