<?php

namespace App\Http\Resources\Subcategoria;

use App\Http\Resources\Categoria\CategoriaResource;
use App\Models\Subcategoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subcategoria */
class SubcategoriaResource extends JsonResource
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
            'categoria_id' => $this->categoria_id,
            'nombre' => $this->nombre,
            'categoria' => new CategoriaResource(
                $this->whenLoaded('categoria'),
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
