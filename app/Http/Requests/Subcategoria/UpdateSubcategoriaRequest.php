<?php

namespace App\Http\Requests\Subcategoria;

use App\Models\Subcategoria;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSubcategoriaRequest extends FormRequest
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
                        ->where('user_id', $userId),
                ),
            ],
            'nombre' => ['sometimes', 'string', 'max:80'],
        ];
    }

    /**
     * Ensure the effective category and name remain unique for this user.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $userId = $this->user()->getAuthIdentifier();
                $subcategoriaId = (int) $this->route('subcategoria');

                $subcategoria = Subcategoria::query()
                    ->whereKey($subcategoriaId)
                    ->whereHas(
                        'categoria',
                        fn ($query) => $query->where('user_id', $userId),
                    )
                    ->first();

                if (! $subcategoria) {
                    return;
                }

                $categoriaId = $this->integer(
                    'categoria_id',
                    $subcategoria->categoria_id,
                );
                $nombre = $this->input('nombre', $subcategoria->nombre);

                $duplicada = Subcategoria::query()
                    ->where('categoria_id', $categoriaId)
                    ->where('nombre', $nombre)
                    ->whereKeyNot($subcategoria->id)
                    ->whereHas(
                        'categoria',
                        fn ($query) => $query->where('user_id', $userId),
                    )
                    ->exists();

                if ($duplicada) {
                    $validator->errors()->add(
                        'nombre',
                        'Ya existe una subcategoría con este nombre en la categoría seleccionada.',
                    );
                }
            },
        ];
    }
}
