<?php

namespace App\Http\Resources\Ingreso;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class IngresoCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var class-string<IngresoResource>
     */
    public $collects = IngresoResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->all();
    }
}
