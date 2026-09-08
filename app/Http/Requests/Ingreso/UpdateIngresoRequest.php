<?php

namespace App\Http\Requests\Ingreso;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIngresoRequest extends FormRequest
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
                'sometimes',
                'integer',
                Rule::exists('categorias', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('tipo', 'ingreso')
                        ->where(
                            fn (Builder $query): Builder => $query
                                ->where('user_id', $userId)
                                ->orWhereNull('user_id'),
                        ),
                ),
            ],
            'fecha' => ['sometimes', 'date_format:Y-m-d'],
            'fuente' => ['sometimes', 'string', 'max:150'],
            'monto' => [
                'sometimes',
                'decimal:0,2',
                'regex:/^(?:0|[1-9]\d{0,9})(?:\.\d{1,2})?$/',
            ],
            'notas' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
