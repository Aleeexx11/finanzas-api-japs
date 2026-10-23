<?php

namespace App\Http\Requests\Presupuesto;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePresupuestoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $userId = $this->user()->getAuthIdentifier();
        $presupuestoId = $this->route('presupuesto');

        return [
            'categoria_id' => [
                'required',
                'integer',
                Rule::exists('categorias', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('tipo', 'egreso')
                        ->where(fn (Builder $query): Builder => $query
                            ->where('user_id', $userId)
                            ->orWhereNull('user_id')),
                ),
                Rule::unique('presupuestos')->ignore($presupuestoId)->where(
                    fn (Builder $query): Builder => $query
                        ->where('categoria_id', $this->input('categoria_id'))
                        ->where('user_id', $userId)
                        ->where('periodo_inicio', $this->input('periodo_inicio')),
                ),
            ],
            'periodo_inicio' => ['required', 'date_format:Y-m-d', 'regex:/^\d{4}-(0[1-9]|1[0-2])-01$/'],
            'monto_limite' => [
                'required',
                'decimal:0,2',
                'gt:0',
                'regex:/^(?:0|[1-9]\d{0,9})(?:\.\d{1,2})?$/',
            ],
            'alerta_porcentaje' => ['required', 'integer', 'between:1,100'],
            'alerta_activa' => ['required', 'boolean'],
        ];
    }
}
