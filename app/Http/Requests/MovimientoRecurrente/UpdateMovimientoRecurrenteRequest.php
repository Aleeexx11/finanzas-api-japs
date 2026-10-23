<?php

namespace App\Http\Requests\MovimientoRecurrente;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMovimientoRecurrenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $userId = $this->user()->getAuthIdentifier();

        return [
            'tipo' => ['required', 'in:ingreso,egreso'],
            'categoria_id' => [
                'required',
                'integer',
                Rule::exists('categorias', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('tipo', $this->input('tipo'))
                        ->where(fn (Builder $query): Builder => $query
                            ->where('user_id', $userId)
                            ->orWhereNull('user_id')),
                ),
            ],
            'subcategoria_id' => [
                'nullable',
                'integer',
                Rule::exists('subcategorias', 'id')->where(
                    fn (Builder $query): Builder => $query->where('categoria_id', $this->input('categoria_id')),
                ),
                'prohibited_if:tipo,ingreso',
            ],
            'concepto' => ['required', 'string', 'max:150'],
            'monto' => ['required', 'decimal:0,2', 'regex:/^(?:0|[1-9]\d{0,9})(?:\.\d{1,2})?$/'],
            'proxima_fecha' => ['required', 'date_format:Y-m-d'],
            'activo' => ['required', 'boolean'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
