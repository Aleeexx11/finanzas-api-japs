<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Egreso\IndexEgresoRequest;
use App\Http\Requests\Egreso\StoreEgresoRequest;
use App\Http\Requests\Egreso\UpdateEgresoRequest;
use App\Http\Resources\Egreso\EgresoCollection;
use App\Http\Resources\Egreso\EgresoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EgresoController extends Controller
{
    /**
     * Display the authenticated user's expenses.
     */
    public function index(IndexEgresoRequest $request): EgresoCollection
    {
        $filters = $request->validated();

        $query = $request->user()
            ->egresos()
            ->with(['categoria', 'subcategoria']);

        if (array_key_exists('anio', $filters)) {
            $query->whereYear('fecha', $filters['anio']);
        }

        if (array_key_exists('mes', $filters)) {
            $query->whereMonth('fecha', $filters['mes']);
        }

        return new EgresoCollection(
            $query->orderByDesc('fecha')->get(),
        );
    }

    /**
     * Store a newly created expense for the authenticated user.
     */
    public function store(StoreEgresoRequest $request): JsonResponse
    {
        $egreso = $request->user()
            ->egresos()
            ->create($request->validated());

        $egreso->load(['categoria', 'subcategoria']);

        return (new EgresoResource($egreso))
            ->additional(['message' => 'Egreso creado correctamente.'])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display an expense owned by the authenticated user.
     */
    public function show(Request $request, int $egreso): EgresoResource
    {
        $egreso = $request->user()
            ->egresos()
            ->with(['categoria', 'subcategoria'])
            ->whereKey($egreso)
            ->firstOrFail();

        return new EgresoResource($egreso);
    }

    /**
     * Update an expense owned by the authenticated user.
     */
    public function update(
        UpdateEgresoRequest $request,
        int $egreso,
    ): JsonResponse {
        $egreso = $request->user()
            ->egresos()
            ->whereKey($egreso)
            ->firstOrFail();

        $egreso->update($request->validated());
        $egreso->load(['categoria', 'subcategoria']);

        return (new EgresoResource($egreso))
            ->additional(['message' => 'Egreso actualizado correctamente.'])
            ->response();
    }

    /**
     * Remove an expense owned by the authenticated user.
     */
    public function destroy(Request $request, int $egreso): Response
    {
        $egreso = $request->user()
            ->egresos()
            ->whereKey($egreso)
            ->firstOrFail();

        $egreso->delete();

        return response()->noContent();
    }
}
