<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Categoria\IndexCategoriaRequest;
use App\Http\Requests\Categoria\StoreCategoriaRequest;
use App\Http\Requests\Categoria\UpdateCategoriaRequest;
use App\Http\Resources\Categoria\CategoriaCollection;
use App\Http\Resources\Categoria\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoriaController extends Controller
{
    /**
     * Display the system categories and the authenticated user's categories.
     */
    public function index(IndexCategoriaRequest $request): CategoriaCollection
    {
        $filters = $request->validated();
        $userId = $request->user()->getAuthIdentifier();

        $query = Categoria::query()
            ->where(
                fn (Builder $query): Builder => $query
                    ->where('user_id', $userId)
                    ->orWhereNull('user_id'),
            )
            ->with('subcategorias');

        if (array_key_exists('tipo', $filters)) {
            $query->where('tipo', $filters['tipo']);
        }

        return new CategoriaCollection(
            $query->orderBy('tipo')->orderBy('nombre')->get(),
        );
    }

    /**
     * Store a personal category for the authenticated user.
     */
    public function store(StoreCategoriaRequest $request): JsonResponse
    {
        $categoria = $request->user()
            ->categorias()
            ->create($request->validated());

        $categoria->load('subcategorias');

        return (new CategoriaResource($categoria))
            ->additional(['message' => 'Categoría creada correctamente.'])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display an accessible system or personal category.
     */
    public function show(Request $request, int $categoria): CategoriaResource
    {
        $categoria = $this->accessibleCategories($request)
            ->with('subcategorias')
            ->whereKey($categoria)
            ->firstOrFail();

        return new CategoriaResource($categoria);
    }

    /**
     * Update a personal category owned by the authenticated user.
     */
    public function update(
        UpdateCategoriaRequest $request,
        int $categoria,
    ): JsonResponse {
        $categoria = $request->user()
            ->categorias()
            ->whereKey($categoria)
            ->firstOrFail();

        $categoria->update($request->validated());
        $categoria->load('subcategorias');

        return (new CategoriaResource($categoria))
            ->additional(['message' => 'Categoría actualizada correctamente.'])
            ->response();
    }

    /**
     * Remove an unused personal category owned by the authenticated user.
     */
    public function destroy(Request $request, int $categoria): JsonResponse|Response
    {
        $userId = $request->user()->getAuthIdentifier();

        $categoria = $request->user()
            ->categorias()
            ->whereKey($categoria)
            ->firstOrFail();

        $estaEnUso = $categoria->ingresos()
            ->where('user_id', $userId)
            ->exists()
            || $categoria->egresos()
                ->where('user_id', $userId)
                ->exists();

        if ($estaEnUso) {
            return response()->json([
                'message' => 'No puedes eliminar una categoría que está en uso.',
            ], Response::HTTP_CONFLICT);
        }

        $categoria->delete();

        return response()->noContent();
    }

    /**
     * Build a query limited to system and authenticated-user categories.
     */
    private function accessibleCategories(Request $request): Builder
    {
        $userId = $request->user()->getAuthIdentifier();

        return Categoria::query()->where(
            fn (Builder $query): Builder => $query
                ->where('user_id', $userId)
                ->orWhereNull('user_id'),
        );
    }
}
