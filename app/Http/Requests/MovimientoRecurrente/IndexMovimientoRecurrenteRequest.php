<?php

namespace App\Http\Requests\MovimientoRecurrente;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexMovimientoRecurrenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'tipo' => ['sometimes', 'in:ingreso,egreso'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
