<?php

namespace App\Http\Requests\MetaAhorro;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMetaAhorroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'monto_objetivo' => ['required', 'decimal:0,2', 'gt:0', 'regex:/^(?:0|[1-9]\d{0,9})(?:\.\d{1,2})?$/'],
            'monto_ahorrado' => ['sometimes', 'decimal:0,2', 'gte:0', 'regex:/^(?:0|[1-9]\d{0,9})(?:\.\d{1,2})?$/'],
            'fecha_objetivo' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
