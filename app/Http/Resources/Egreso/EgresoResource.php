<?php

namespace App\Http\Resources\Egreso;

use App\Models\Egreso;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Egreso */
class EgresoResource extends JsonResource
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
            'categoria_id' => $this->categoria_id,
            'subcategoria_id' => $this->subcategoria_id,
            'fecha' => $this->fecha?->toDateString(),
            'descripcion' => $this->descripcion,
            'monto' => $this->monto,
            'notas' => $this->notas,
            'categoria' => $this->whenLoaded('categoria', fn (): ?array => $this->categoria ? [
                'id' => $this->categoria->id,
                'nombre' => $this->categoria->nombre,
                'tipo' => $this->categoria->tipo,
            ] : null),
            'subcategoria' => $this->whenLoaded('subcategoria', fn (): ?array => $this->subcategoria ? [
                'id' => $this->subcategoria->id,
                'categoria_id' => $this->subcategoria->categoria_id,
                'nombre' => $this->subcategoria->nombre,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
