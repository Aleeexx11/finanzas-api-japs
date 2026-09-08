<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subcategoria\IndexSubcategoriaRequest;
use App\Http\Requests\Subcategoria\StoreSubcategoriaRequest;
use App\Http\Requests\Subcategoria\UpdateSubcategoriaRequest;
use App\Http\Resources\Subcategoria\SubcategoriaCollection;
use App\Http\Resources\Subcategoria\SubcategoriaResource;
use App\Models\Categoria;
use App\Models\Subcategoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubcategoriaController extends Controller
{
    /**
     * Display accessible expense subcategories.
     */
    public function index(IndexSubcategoriaRequest $request): SubcategoriaCollection
    {
        $filters = $request->validated();
        $userId = $request->user()->getAuthIdentifier();

        $query = Subcategoria::query()
            ->whereHas(
                'categoria',
                fn (Builder $query): Builder => $query
                    ->where('tipo', 'egreso')
                    ->where(
                        fn (Builder $query): Builder => $query
                            ->where('user_id', $userId)
                            ->orWhereNull('user_id'),
                    ),
            )
            ->with('categoria');

        if (array_key_exists('categoria_id', $filters)) {
            $query->where('categoria_id', $filters['categoria_id']);
        }

        return new SubcategoriaCollection(
            $query->orderBy('nombre')->get(),
        );
    }

    /**
     * Display the subcategories of an accessible expense category.
     */
    public function indexByCategoria(
        IndexSubcategoriaRequest $request,
        int $categoria,
    ): SubcategoriaCollection {
        $userId = $request->user()->getAuthIdentifier();

        $categoria = Categoria::query()
            ->whereKey($categoria)
            ->where('tipo', 'egreso')
            ->where(
                fn (Builder $query): Builder => $query
                    ->where('user_id', $userId)
                    ->orWhereNull('user_id'),
            )
            ->firstOrFail();

        return new SubcategoriaCollection(
            $categoria->subcategorias()
                ->with('categoria')
                ->orderBy('nombre')
                ->get(),
        );
    }

    /**
     * Store a subcategory inside a personal expense category.
     */
    public function store(
        StoreSubcategoriaRequest $request,
        ?int $categoria = null,
    ): JsonResponse {
        $data = $request->validated();

        $categoria = $request->user()
            ->categorias()
            ->where('tipo', 'egreso')
            ->whereKey($data['categoria_id'])
            ->firstOrFail();

        $subcategoria = $categoria->subcategorias()->create([
            'nombre' => $data['nombre'],
        ]);
        $subcategoria->load('categoria');

        return (new SubcategoriaResource($subcategoria))
            ->additional(['message' => 'Subcategoría creada correctamente.'])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display an accessible system or personal subcategory.
     */
    public function show(Request $request, int $subcategoria): SubcategoriaResource
    {
        $subcategoria = $this->accessibleSubcategories($request)
            ->with('categoria')
            ->whereKey($subcategoria)
            ->firstOrFail();

        return new SubcategoriaResource($subcategoria);
    }

    /**
     * Update a subcategory from a category owned by the authenticated user.
     */
    public function update(
        UpdateSubcategoriaRequest $request,
        int $subcategoria,
    ): JsonResponse {
        $subcategoria = $this->personalSubcategories($request)
            ->whereKey($subcategoria)
            ->firstOrFail();

        $subcategoria->update($request->validated());
        $subcategoria->load('categoria');

        return (new SubcategoriaResource($subcategoria))
            ->additional(['message' => 'Subcategoría actualizada correctamente.'])
            ->response();
    }

    /**
     * Remove an unused subcategory owned through a personal category.
     */
    public function destroy(Request $request, int $subcategoria): JsonResponse|Response
    {
        $userId = $request->user()->getAuthIdentifier();

        $subcategoria = $this->personalSubcategories($request)
            ->whereKey($subcategoria)
            ->firstOrFail();

        $estaEnUso = $subcategoria->egresos()
            ->where('user_id', $userId)
            ->exists();

        if ($estaEnUso) {
            return response()->json([
                'message' => 'No puedes eliminar una subcategoría que está en uso.',
            ], Response::HTTP_CONFLICT);
        }

        $subcategoria->delete();

        return response()->noContent();
    }

    /**
     * Build a query limited to accessible system and personal subcategories.
     */
    private function accessibleSubcategories(Request $request): Builder
    {
        $userId = $request->user()->getAuthIdentifier();

        return Subcategoria::query()->whereHas(
            'categoria',
            fn (Builder $query): Builder => $query
                ->where('tipo', 'egreso')
                ->where(
                    fn (Builder $query): Builder => $query
                        ->where('user_id', $userId)
                        ->orWhereNull('user_id'),
                ),
        );
    }

    /**
     * Build a query limited to subcategories owned through personal categories.
     */
    private function personalSubcategories(Request $request): Builder
    {
        $userId = $request->user()->getAuthIdentifier();

        return Subcategoria::query()->whereHas(
            'categoria',
            fn (Builder $query): Builder => $query
                ->where('tipo', 'egreso')
                ->where('user_id', $userId),
        );
    }
}
