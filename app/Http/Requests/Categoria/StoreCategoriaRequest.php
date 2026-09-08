<?php

namespace App\Http\Requests\Categoria;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoriaRequest extends FormRequest
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
            'nombre' => [
                'required',
                'string',
                'max:80',
                Rule::unique('categorias', 'nombre')->where(
                    fn (Builder $query): Builder => $query
                        ->where('user_id', $userId)
                        ->where('tipo', $this->input('tipo')),
                ),
            ],
            'tipo' => [
                'required',
                Rule::in(['ingreso', 'egreso']),
            ],
        ];
    }
}
