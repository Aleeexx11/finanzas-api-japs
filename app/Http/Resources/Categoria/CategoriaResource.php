<?php

namespace App\Http\Resources\Categoria;

use App\Http\Resources\Subcategoria\SubcategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Categoria */
class CategoriaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'es_sistema' => $this->user_id === null,
            'subcategorias' => SubcategoriaResource::collection(
                $this->whenLoaded('subcategorias'),
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
