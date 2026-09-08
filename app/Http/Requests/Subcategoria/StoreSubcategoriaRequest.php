<?php

namespace App\Http\Requests\Subcategoria;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubcategoriaRequest extends FormRequest
{
    /**
     * Use the category from a nested route when it is present.
     */
    protected function prepareForValidation(): void
    {
        if ($this->route('categoria') !== null) {
            $this->merge([
                'categoria_id' => (int) $this->route('categoria'),
            ]);
        }
    }

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
                        ->where('user_id', $userId),
                ),
            ],
            'nombre' => [
                'required',
                'string',
                'max:80',
                Rule::unique('subcategorias', 'nombre')->where(
                    fn (Builder $query): Builder => $query->where(
                        'categoria_id',
                        $this->integer('categoria_id'),
                    ),
                ),
            ],
        ];
    }
}
