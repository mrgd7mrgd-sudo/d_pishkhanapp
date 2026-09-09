<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class OperatorLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request (§7.2: min 12 characters password).
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'office_code' => ['required', 'string', 'size:4'],
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:12'],
        ];
    }
}
