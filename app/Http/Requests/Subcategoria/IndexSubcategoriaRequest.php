<?php

namespace App\Http\Requests\Subcategoria;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSubcategoriaRequest extends FormRequest
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
                        ->where('tipo', 'egreso')
                        ->where(
                            fn (Builder $query): Builder => $query
                                ->where('user_id', $userId)
                                ->orWhereNull('user_id'),
                        ),
                ),
            ],
        ];
    }
}
