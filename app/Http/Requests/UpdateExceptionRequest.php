<?php

namespace App\Http\Requests;

use App\Enums\ExceptionStatus;
use App\Enums\Severity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exception = $this->route('exception');

        return Gate::forUser($this->user())->allows('manageExceptions', $exception->shipment);
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(ExceptionStatus::class)],
            'severity' => ['sometimes', Rule::enum(Severity::class)],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
