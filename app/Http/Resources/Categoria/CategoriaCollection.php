<?php

namespace App\Http\Resources\Categoria;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoriaCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var class-string<CategoriaResource>
     */
    public $collects = CategoriaResource::class;

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
