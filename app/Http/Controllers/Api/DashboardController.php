<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\EgresosPorCategoriaRequest;
use App\Http\Requests\Dashboard\ResumenAnualDashboardRequest;
use App\Http\Requests\Dashboard\ResumenDashboardRequest;
use App\Models\Categoria;
use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Return all twelve monthly totals for the requested year.
     */
    public function resumenAnual(
        ResumenAnualDashboardRequest $request,
    ): JsonResponse {
        $anio = (int) $request->validated('anio');
        $inicioAnio = new DateTimeImmutable(sprintf('%04d-01-01', $anio));
        $inicioAnioSiguiente = $inicioAnio->modify('+1 year');

        $movimientos = $this->movimientosDelPeriodo(
            $request->user()->getKey(),
            $inicioAnio,
            $inicioAnioSiguiente,
        );
        $expresionMes = DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', fecha) AS INTEGER)"
            : 'MONTH(fecha)';

        $totalesPorMes = DB::query()
            ->fromSub($movimientos, 'movimientos')
            ->selectRaw("{$expresionMes} AS mes")
            ->selectRaw(
                'SUM(CASE WHEN tipo = ? THEN monto ELSE 0 END) AS ingresos',
                ['ingreso'],
            )
            ->selectRaw(
                'SUM(CASE WHEN tipo = ? THEN monto ELSE 0 END) AS egresos',
                ['egreso'],
            )
            ->groupByRaw($expresionMes)
            ->get()
            ->keyBy('mes');

        $resumen = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $totales = $totalesPorMes->get($mes);
            $ingresos = $this->aCentavos($totales?->ingresos);
            $egresos = $this->aCentavos($totales?->egresos);

            $resumen[] = [
                'mes' => $mes,
                'ingresos' => $this->formatearCentavos($ingresos),
                'egresos' => $this->formatearCentavos($egresos),
                'balance' => $this->formatearCentavos($ingresos - $egresos),
            ];
        }

        return response()->json($resumen);
    }

    /**
     * Return the authenticated user's expenses grouped by category for a month.
     */
    public function egresosPorCategoria(
        EgresosPorCategoriaRequest $request,
    ): JsonResponse {
        $filters = $request->validated();
        $anio = (int) $filters['anio'];
        $mes = (int) $filters['mes'];

        $inicioMes = new DateTimeImmutable(sprintf('%04d-%02d-01', $anio, $mes));
        $inicioMesSiguiente = $inicioMes->modify('+1 month');

        $categorias = Categoria::query()
            ->join('egresos', 'egresos.categoria_id', '=', 'categorias.id')
            ->where('egresos.user_id', $request->user()->getKey())
            ->where('egresos.fecha', '>=', $inicioMes->format('Y-m-d'))
            ->where('egresos.fecha', '<', $inicioMesSiguiente->format('Y-m-d'))
            ->select(['categorias.id', 'categorias.nombre'])
            ->selectRaw('SUM(egresos.monto) AS total')
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderByDesc('total')
            ->get()
            ->map(fn (Categoria $categoria): array => [
                'id' => $categoria->getKey(),
                'nombre' => $categoria->nombre,
                'total' => $this->formatearCentavos(
                    $this->aCentavos($categoria->getAttribute('total')),
                ),
            ]);

        return response()->json($categorias);
    }

    /**
     * Return the authenticated user's monthly and year-to-date summary.
     */
    public function resumen(ResumenDashboardRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $anio = (int) $filters['anio'];
        $mes = (int) $filters['mes'];
        $userId = $request->user()->getKey();

        $inicioAnio = new DateTimeImmutable(sprintf('%04d-01-01', $anio));
        $inicioMes = new DateTimeImmutable(sprintf('%04d-%02d-01', $anio, $mes));
        $inicioMesSiguiente = $inicioMes->modify('+1 month');

        $movimientos = $this->movimientosDelPeriodo(
            $userId,
            $inicioAnio,
            $inicioMesSiguiente,
        );

        $totales = DB::query()
            ->fromSub($movimientos, 'movimientos')
            ->selectRaw(
                <<<'SQL'
                COALESCE(SUM(CASE
                    WHEN tipo = ? AND fecha >= ? THEN monto
                    ELSE 0
                END), 0) AS ingresos_mes,
                COALESCE(SUM(CASE
                    WHEN tipo = ? AND fecha >= ? THEN monto
                    ELSE 0
                END), 0) AS egresos_mes,
                COALESCE(SUM(CASE WHEN tipo = ? THEN monto ELSE 0 END), 0) AS ingresos_acumulados,
                COALESCE(SUM(CASE WHEN tipo = ? THEN monto ELSE 0 END), 0) AS egresos_acumulados
                SQL,
                [
                    'ingreso',
                    $inicioMes->format('Y-m-d'),
                    'egreso',
                    $inicioMes->format('Y-m-d'),
                    'ingreso',
                    'egreso',
                ],
            )
            ->first();

        $ingresosMes = $this->aCentavos($totales?->ingresos_mes);
        $egresosMes = $this->aCentavos($totales?->egresos_mes);
        $ingresosAcumulados = $this->aCentavos($totales?->ingresos_acumulados);
        $egresosAcumulados = $this->aCentavos($totales?->egresos_acumulados);

        return response()->json([
            'ingresos_mes' => $this->formatearCentavos($ingresosMes),
            'egresos_mes' => $this->formatearCentavos($egresosMes),
            'balance_mes' => $this->formatearCentavos($ingresosMes - $egresosMes),
            'ingresos_acumulados' => $this->formatearCentavos($ingresosAcumulados),
            'egresos_acumulados' => $this->formatearCentavos($egresosAcumulados),
            'balance_acumulado' => $this->formatearCentavos(
                $ingresosAcumulados - $egresosAcumulados,
            ),
            'porcentaje_gastado' => $this->porcentajeGastado(
                $egresosMes,
                $ingresosMes,
            ),
        ]);
    }

    /**
     * Build the income and expense dataset used by the aggregate query.
     */
    private function movimientosDelPeriodo(
        int $userId,
        DateTimeImmutable $inicioAnio,
        DateTimeImmutable $inicioMesSiguiente,
    ): Builder {
        $inicio = $inicioAnio->format('Y-m-d');
        $finExclusivo = $inicioMesSiguiente->format('Y-m-d');

        $ingresos = DB::table('ingresos')
            ->selectRaw('? AS tipo, monto, fecha', ['ingreso'])
            ->where('user_id', $userId)
            ->where('fecha', '>=', $inicio)
            ->where('fecha', '<', $finExclusivo);

        return DB::table('egresos')
            ->selectRaw('? AS tipo, monto, fecha', ['egreso'])
            ->where('user_id', $userId)
            ->where('fecha', '>=', $inicio)
            ->where('fecha', '<', $finExclusivo)
            ->unionAll($ingresos);
    }

    /**
     * Convert a database DECIMAL value to integer cents without using floats.
     */
    private function aCentavos(mixed $monto): int
    {
        $monto = (string) ($monto ?? '0');

        if (! preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/', $monto, $partes)) {
            return 0;
        }

        $centavos = ((int) $partes[2] * 100)
            + (int) str_pad($partes[3] ?? '', 2, '0');

        return ($partes[1] ?? '') === '-' ? -$centavos : $centavos;
    }

    /**
     * Format integer cents as an exact decimal string for JSON transport.
     */
    private function formatearCentavos(int $centavos): string
    {
        $signo = $centavos < 0 ? '-' : '';
        $centavos = abs($centavos);

        return sprintf('%s%d.%02d', $signo, intdiv($centavos, 100), $centavos % 100);
    }

    /**
     * Calculate a percentage with two decimal places and no division by zero.
     */
    private function porcentajeGastado(int $egresos, int $ingresos): string
    {
        if ($ingresos === 0) {
            return '0.00';
        }

        $porcentajeEnCentesimas = intdiv(
            ($egresos * 10000) + intdiv($ingresos, 2),
            $ingresos,
        );

        return $this->formatearCentavos($porcentajeEnCentesimas);
    }
}
