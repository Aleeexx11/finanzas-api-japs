<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MovimientoRecurrente\IndexMovimientoRecurrenteRequest;
use App\Http\Requests\MovimientoRecurrente\StoreMovimientoRecurrenteRequest;
use App\Http\Requests\MovimientoRecurrente\UpdateEstadoMovimientoRecurrenteRequest;
use App\Http\Requests\MovimientoRecurrente\UpdateMovimientoRecurrenteRequest;
use App\Models\MovimientoRecurrente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MovimientoRecurrenteController extends Controller
{
    public function index(IndexMovimientoRecurrenteRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = $request->user()
            ->movimientosRecurrentes()
            ->with(['categoria:id,nombre', 'subcategoria:id,nombre']);

        if (isset($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (array_key_exists('activo', $filters)) {
            $query->where('activo', $filters['activo']);
        }

        return response()->json($query->orderBy('proxima_fecha')->get()->map(
            fn (MovimientoRecurrente $movimiento): array => $this->representar($movimiento),
        )->values());
    }

    public function store(StoreMovimientoRecurrenteRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $datos['dia_programado'] = Carbon::parse($datos['proxima_fecha'])->day;
        $movimiento = $request->user()
            ->movimientosRecurrentes()
            ->create([...$datos, 'activo' => true])
            ->load(['categoria:id,nombre', 'subcategoria:id,nombre']);

        return response()->json($this->representar($movimiento), 201);
    }

    public function update(UpdateMovimientoRecurrenteRequest $request, int $movimientoRecurrente): JsonResponse
    {
        $movimiento = $request->user()
            ->movimientosRecurrentes()
            ->whereKey($movimientoRecurrente)
            ->firstOrFail();

        $datos = $request->validated();
        $datos['dia_programado'] = Carbon::parse($datos['proxima_fecha'])->day;
        $movimiento->update($datos);
        $movimiento->load(['categoria:id,nombre', 'subcategoria:id,nombre']);

        return response()->json($this->representar($movimiento));
    }

    public function estado(
        UpdateEstadoMovimientoRecurrenteRequest $request,
        int $movimientoRecurrente,
    ): JsonResponse {
        $movimiento = $request->user()
            ->movimientosRecurrentes()
            ->whereKey($movimientoRecurrente)
            ->firstOrFail();

        $movimiento->update($request->validated());
        $movimiento->load(['categoria:id,nombre', 'subcategoria:id,nombre']);

        return response()->json($this->representar($movimiento));
    }

    public function confirmar(Request $request, int $movimientoRecurrente): JsonResponse
    {
        $result = DB::transaction(function () use ($request, $movimientoRecurrente): array {
            $programacion = $request->user()
                ->movimientosRecurrentes()
                ->whereKey($movimientoRecurrente)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($programacion->activo, 409, 'Esta programación está pausada.');
            abort_if($programacion->proxima_fecha->isFuture(), 422, 'Este movimiento todavía no llega a su fecha programada.');

            $fecha = $programacion->proxima_fecha->format('Y-m-d');
            if ($programacion->tipo === 'ingreso') {
                $movimiento = $request->user()->ingresos()->create([
                    'categoria_id' => $programacion->categoria_id,
                    'fecha' => $fecha,
                    'fuente' => $programacion->concepto,
                    'monto' => $programacion->monto,
                    'notas' => $programacion->notas,
                ]);
            } else {
                $movimiento = $request->user()->egresos()->create([
                    'categoria_id' => $programacion->categoria_id,
                    'subcategoria_id' => $programacion->subcategoria_id,
                    'fecha' => $fecha,
                    'descripcion' => $programacion->concepto,
                    'monto' => $programacion->monto,
                    'notas' => $programacion->notas,
                ]);
            }

            $siguienteMes = Carbon::parse($programacion->proxima_fecha)
                ->addMonthNoOverflow()
                ->startOfMonth();
            $siguienteFecha = $siguienteMes
                ->day(min($programacion->dia_programado, $siguienteMes->daysInMonth))
                ->toDateString();
            $programacion->proxima_fecha = $siguienteFecha;
            $programacion->save();

            return ['movimiento' => $movimiento, 'programacion' => $programacion, 'fecha' => $fecha];
        });

        return response()->json([
            'movimiento_id' => $result['movimiento']->getKey(),
            'tipo' => $result['programacion']->tipo,
            'fecha' => $result['fecha'],
            'proxima_fecha' => $result['programacion']->proxima_fecha->format('Y-m-d'),
        ], 201);
    }

    public function destroy(Request $request, int $movimientoRecurrente): Response
    {
        $request->user()
            ->movimientosRecurrentes()
            ->whereKey($movimientoRecurrente)
            ->firstOrFail()
            ->delete();

        return response()->noContent();
    }

    private function representar(MovimientoRecurrente $movimiento): array
    {
        return [
            'id' => $movimiento->getKey(),
            'tipo' => $movimiento->tipo,
            'categoria_id' => $movimiento->categoria_id,
            'categoria' => $movimiento->categoria?->nombre,
            'subcategoria_id' => $movimiento->subcategoria_id,
            'subcategoria' => $movimiento->subcategoria?->nombre,
            'concepto' => $movimiento->concepto,
            'monto' => $movimiento->monto,
            'proxima_fecha' => $movimiento->proxima_fecha->format('Y-m-d'),
            'activo' => $movimiento->activo,
            'notas' => $movimiento->notas,
        ];
    }
}
