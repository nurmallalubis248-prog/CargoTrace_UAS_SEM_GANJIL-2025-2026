<?php

namespace App\Http\Requests;

use App\Enums\ExceptionType;
use App\Enums\Severity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->allows('manageExceptions', $this->route('shipment'));
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ExceptionType::class)],
            'severity' => ['required', Rule::enum(Severity::class)],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }
}
