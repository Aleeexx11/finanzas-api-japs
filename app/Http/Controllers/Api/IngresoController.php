<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ingreso\IndexIngresoRequest;
use App\Http\Requests\Ingreso\StoreIngresoRequest;
use App\Http\Requests\Ingreso\UpdateIngresoRequest;
use App\Http\Resources\Ingreso\IngresoCollection;
use App\Http\Resources\Ingreso\IngresoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IngresoController extends Controller
{
    /**
     * Display the authenticated user's incomes.
     */
    public function index(IndexIngresoRequest $request): IngresoCollection
    {
        $filters = $request->validated();

        $query = $request->user()
            ->ingresos()
            ->with('categoria');

        if (array_key_exists('anio', $filters)) {
            $query->whereYear('fecha', $filters['anio']);
        }

        if (array_key_exists('mes', $filters)) {
            $query->whereMonth('fecha', $filters['mes']);
        }

        return new IngresoCollection(
            $query->orderByDesc('fecha')->get(),
        );
    }

    /**
     * Store a newly created income for the authenticated user.
     */
    public function store(StoreIngresoRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $recurrente = (bool) ($datos['recurrente'] ?? false);
        unset($datos['recurrente']);

        $ingreso = DB::transaction(function () use ($request, $datos, $recurrente) {
            $ingreso = $request->user()->ingresos()->create($datos);

            if ($recurrente) {
                $request->user()->movimientosRecurrentes()->create([
                    'tipo' => 'ingreso',
                    'categoria_id' => $ingreso->categoria_id,
                    'concepto' => $ingreso->fuente,
                    'monto' => $ingreso->monto,
                    'proxima_fecha' => Carbon::parse($ingreso->fecha)->addMonthNoOverflow()->toDateString(),
                    'dia_programado' => Carbon::parse($ingreso->fecha)->day,
                    'activo' => true,
                    'notas' => $ingreso->notas,
                ]);
            }

            return $ingreso;
        });

        $ingreso->load('categoria');

        return (new IngresoResource($ingreso))
            ->additional(['message' => 'Ingreso creado correctamente.'])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display an income owned by the authenticated user.
     */
    public function show(Request $request, int $ingreso): IngresoResource
    {
        $ingreso = $request->user()
            ->ingresos()
            ->with('categoria')
            ->whereKey($ingreso)
            ->firstOrFail();

        return new IngresoResource($ingreso);
    }

    /**
     * Update an income owned by the authenticated user.
     */
    public function update(
        UpdateIngresoRequest $request,
        int $ingreso,
    ): JsonResponse {
        $ingreso = $request->user()
            ->ingresos()
            ->whereKey($ingreso)
            ->firstOrFail();

        $ingreso->update($request->validated());
        $ingreso->load('categoria');

        return (new IngresoResource($ingreso))
            ->additional(['message' => 'Ingreso actualizado correctamente.'])
            ->response();
    }

    /**
     * Remove an income owned by the authenticated user.
     */
    public function destroy(Request $request, int $ingreso): Response
    {
        $ingreso = $request->user()
            ->ingresos()
            ->whereKey($ingreso)
            ->firstOrFail();

        $ingreso->delete();

        return response()->noContent();
    }
}
