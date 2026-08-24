<?php

namespace App\Http\Requests\Egreso;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEgresoRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->getAuthIdentifier();

        return [
            'categoria_id' => [
                'required',
                'integer',
                Rule::exists('categorias', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('tipo', 'egreso')
                        ->where(
                            fn (Builder $query): Builder => $query
                                ->where('user_id', $userId)
                                ->orWhereNull('user_id'),
                        ),
                ),
            ],
            'subcategoria_id' => [
                'nullable',
                'integer',
                Rule::exists('subcategorias', 'id')->where(
                    fn (Builder $query): Builder => $query->where(
                        'categoria_id',
                        $this->integer('categoria_id'),
                    ),
                ),
            ],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'descripcion' => ['required', 'string', 'max:150'],
            'monto' => [
                'required',
                'decimal:0,2',
                'regex:/^(?:0|[1-9]\d{0,9})(?:\.\d{1,2})?$/',
            ],
            'notas' => ['nullable', 'string'],
        ];
    }
}
