<?php

namespace App\Http\Requests\Categoria;

use App\Models\Categoria;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\Subcategoria;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoriaRequest extends FormRequest
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
        return [
            'nombre' => ['sometimes', 'string', 'max:80'],
            'tipo' => ['sometimes', Rule::in(['ingreso', 'egreso'])],
        ];
    }

    /**
     * Run ownership-aware consistency checks after basic validation.
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
                $categoriaId = (int) $this->route('categoria');

                $categoria = Categoria::query()
                    ->where('user_id', $userId)
                    ->whereKey($categoriaId)
                    ->first();

                if (! $categoria) {
                    return;
                }

                $nombre = $this->input('nombre', $categoria->nombre);
                $tipo = $this->input('tipo', $categoria->tipo);

                $duplicada = Categoria::query()
                    ->where('user_id', $userId)
                    ->where('nombre', $nombre)
                    ->where('tipo', $tipo)
                    ->whereKeyNot($categoria->id)
                    ->exists();

                if ($duplicada) {
                    $validator->errors()->add(
                        'nombre',
                        'Ya tienes una categoría con este nombre y tipo.',
                    );
                }

                if ($tipo === $categoria->tipo) {
                    return;
                }

                $tieneMovimientos = Ingreso::query()
                    ->where('user_id', $userId)
                    ->where('categoria_id', $categoria->id)
                    ->exists()
                    || Egreso::query()
                        ->where('user_id', $userId)
                        ->where('categoria_id', $categoria->id)
                        ->exists();

                $tieneSubcategorias = Subcategoria::query()
                    ->where('categoria_id', $categoria->id)
                    ->whereHas(
                        'categoria',
                        fn ($query) => $query->where('user_id', $userId),
                    )
                    ->exists();

                if ($tieneMovimientos || $tieneSubcategorias) {
                    $validator->errors()->add(
                        'tipo',
                        'No puedes cambiar el tipo de una categoría que ya está en uso.',
                    );
                }
            },
        ];
    }
}
