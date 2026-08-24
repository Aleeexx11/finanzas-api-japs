<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Egreso\IndexEgresoRequest;
use App\Http\Requests\Egreso\StoreEgresoRequest;
use App\Http\Requests\Egreso\UpdateEgresoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EgresoController extends Controller
{
    /**
     * Display the authenticated user's expenses.
     */
    public function index(IndexEgresoRequest $request): JsonResponse
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

        return response()->json([
            'data' => $query->orderByDesc('fecha')->get(),
        ]);
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

        return response()->json([
            'message' => 'Egreso creado correctamente.',
            'data' => $egreso,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display an expense owned by the authenticated user.
     */
    public function show(Request $request, int $egreso): JsonResponse
    {
        $egreso = $request->user()
            ->egresos()
            ->with(['categoria', 'subcategoria'])
            ->whereKey($egreso)
            ->firstOrFail();

        return response()->json([
            'data' => $egreso,
        ]);
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

        return response()->json([
            'message' => 'Egreso actualizado correctamente.',
            'data' => $egreso,
        ]);
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
