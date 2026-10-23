<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Presupuesto\IndexPresupuestoRequest;
use App\Http\Requests\Presupuesto\StorePresupuestoRequest;
use App\Http\Requests\Presupuesto\UpdatePresupuestoRequest;
use App\Models\Egreso;
use App\Models\Presupuesto;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PresupuestoController extends Controller
{
    public function index(IndexPresupuestoRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $inicio = new DateTimeImmutable(sprintf('%04d-%02d-01', $filters['anio'], $filters['mes']));
        $fin = $inicio->modify('+1 month');
        $userId = $request->user()->getKey();

        $montosPorCategoria = Egreso::query()
            ->where('user_id', $userId)
            ->where('fecha', '>=', $inicio->format('Y-m-d'))
            ->where('fecha', '<', $fin->format('Y-m-d'))
            ->get(['categoria_id', 'monto'])
            ->groupBy('categoria_id')
            ->map(fn ($egresos): int => $egresos->sum(
                fn (Egreso $egreso): int => $this->aCentavos($egreso->monto),
            ));

        $presupuestos = $request->user()
            ->presupuestos()
            ->with('categoria:id,nombre')
            ->whereDate('periodo_inicio', $inicio->format('Y-m-d'))
            ->orderBy('categoria_id')
            ->get();

        return response()->json($presupuestos->map(function (Presupuesto $presupuesto) use ($montosPorCategoria): array {
            $limite = $this->aCentavos($presupuesto->monto_limite);
            $gastado = (int) $montosPorCategoria->get($presupuesto->categoria_id, 0);
            $porcentaje = $limite > 0 ? intdiv(($gastado * 10000) + intdiv($limite, 2), $limite) : 0;

            return [
                'id' => $presupuesto->getKey(),
                'categoria_id' => $presupuesto->categoria_id,
                'categoria' => $presupuesto->categoria?->nombre,
                'periodo_inicio' => $presupuesto->periodo_inicio->format('Y-m-d'),
                'monto_limite' => $this->formatearCentavos($limite),
                'gastado' => $this->formatearCentavos($gastado),
                'disponible' => $this->formatearCentavos($limite - $gastado),
                'porcentaje_gastado' => $this->formatearCentavos($porcentaje),
                'alerta_porcentaje' => $presupuesto->alerta_porcentaje,
                'alerta_activa' => $presupuesto->alerta_activa,
                'alerta' => $presupuesto->alerta_activa && $porcentaje >= ($presupuesto->alerta_porcentaje * 100),
                'excedido' => $gastado > $limite,
            ];
        })->values());
    }

    public function store(StorePresupuestoRequest $request): JsonResponse
    {
        $presupuesto = $request->user()->presupuestos()->create([
            ...$request->validated(),
            'alerta_porcentaje' => $request->validated('alerta_porcentaje', 80),
            'alerta_activa' => $request->validated('alerta_activa', true),
        ])->load('categoria:id,nombre');

        return response()->json([
            'id' => $presupuesto->getKey(),
            'categoria_id' => $presupuesto->categoria_id,
            'categoria' => $presupuesto->categoria?->nombre,
            'periodo_inicio' => $presupuesto->periodo_inicio->format('Y-m-d'),
            'monto_limite' => $presupuesto->monto_limite,
            'alerta_porcentaje' => $presupuesto->alerta_porcentaje,
            'alerta_activa' => $presupuesto->alerta_activa,
        ], 201);
    }

    public function update(UpdatePresupuestoRequest $request, int $presupuesto): JsonResponse
    {
        $model = $request->user()->presupuestos()->whereKey($presupuesto)->firstOrFail();
        $model->update($request->validated());
        $model->load('categoria:id,nombre');

        return response()->json([
            'id' => $model->getKey(),
            'categoria_id' => $model->categoria_id,
            'categoria' => $model->categoria?->nombre,
            'periodo_inicio' => $model->periodo_inicio->format('Y-m-d'),
            'monto_limite' => $model->monto_limite,
            'alerta_porcentaje' => $model->alerta_porcentaje,
            'alerta_activa' => $model->alerta_activa,
        ]);
    }

    public function destroy(Request $request, int $presupuesto): Response
    {
        $request->user()->presupuestos()->whereKey($presupuesto)->firstOrFail()->delete();

        return response()->noContent();
    }

    private function aCentavos(mixed $monto): int
    {
        $monto = (string) $monto;

        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $monto, $partes)) {
            return 0;
        }

        return ((int) $partes[1] * 100) + (int) str_pad($partes[2] ?? '', 2, '0');
    }

    private function formatearCentavos(int $centavos): string
    {
        $signo = $centavos < 0 ? '-' : '';
        $centavos = abs($centavos);

        return sprintf('%s%d.%02d', $signo, intdiv($centavos, 100), $centavos % 100);
    }
}
