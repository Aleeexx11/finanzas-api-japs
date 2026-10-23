<?php

namespace App\Http\Requests\Presupuesto;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexPresupuestoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'anio' => ['required', 'integer', 'between:1900,2100'],
            'mes' => ['required', 'integer', 'between:1,12'],
        ];
    }
}
