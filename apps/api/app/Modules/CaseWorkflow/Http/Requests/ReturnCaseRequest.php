<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Http\Requests;

use App\Modules\CaseWorkflow\Domain\Enums\ReturnReasonCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReturnCaseRequest extends FormRequest
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
            'reason_code' => ['required', 'string', Rule::in(ReturnReasonCode::values())],
            'operator_note' => ['nullable', 'string', 'max:1000'],
            'target_document_type_code' => ['nullable', 'string', 'max:100'],
            'deadline_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ];
    }
}
