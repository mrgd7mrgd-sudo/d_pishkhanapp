<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Http\Requests;

use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Foundation\Http\FormRequest;

final class CreateCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Citizen;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'min:8', 'max:128'],
            'service_id' => ['required', 'string'],
            'dispatch_mode' => ['required', 'string', 'in:auto,manual'],
            'office_id' => ['nullable', 'required_if:dispatch_mode,manual', 'string'],
            'payment_method' => ['required', 'string', 'in:wallet,gateway'],
            'delivery_preference' => ['required', 'string', 'in:in_person,courier,post'],
            'delivery_address_id' => ['nullable', 'string'],
            'on_behalf_of_delegation_id' => ['nullable', 'string'],
            'documents' => ['nullable', 'array'],
            'documents.*.document_type_code' => ['required_with:documents', 'string'],
            'documents.*.upload_id' => ['required_with:documents', 'string'],
            'commitment_signed' => ['required', 'accepted'],
            'citizen_location' => ['nullable', 'array'],
            'citizen_location.lat' => ['required_with:citizen_location', 'numeric'],
            'citizen_location.lng' => ['required_with:citizen_location', 'numeric'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'idempotency_key.required' => 'ارسال هدر Idempotency-Key برای ثبت پرونده الزامی است.',
            'commitment_signed.accepted' => 'پذیرش تعهدنامه قوانین و مقررات الزامی است.',
        ];
    }
}
