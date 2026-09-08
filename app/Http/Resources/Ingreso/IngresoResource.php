<?php

namespace App\Http\Resources\Ingreso;

use App\Models\Ingreso;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Ingreso */
class IngresoResource extends JsonResource
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
            'fecha' => $this->fecha?->toDateString(),
            'fuente' => $this->fuente,
            'monto' => $this->monto,
            'notas' => $this->notas,
            'categoria' => $this->whenLoaded('categoria', fn (): ?array => $this->categoria ? [
                'id' => $this->categoria->id,
                'nombre' => $this->categoria->nombre,
                'tipo' => $this->categoria->tipo,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
