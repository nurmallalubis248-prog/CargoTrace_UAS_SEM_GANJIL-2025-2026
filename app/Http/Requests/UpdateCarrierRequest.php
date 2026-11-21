<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarrierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'alpha_num', 'max:10', Rule::unique('carriers', 'code')->ignore($this->route('carrier'))],
            'transport_mode' => ['required', Rule::in(['SEA', 'AIR', 'ROAD'])],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('code')) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }
    }
}
