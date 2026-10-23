<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MetaAhorro\IndexMetaAhorroRequest;
use App\Http\Requests\MetaAhorro\StoreMetaAhorroRequest;
use App\Http\Requests\MetaAhorro\UpdateMetaAhorroRequest;
use App\Models\MetaAhorro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MetaAhorroController extends Controller
{
    public function index(IndexMetaAhorroRequest $request): JsonResponse
    {
        return response()->json($request->user()
            ->metasAhorro()
            ->orderByRaw('fecha_objetivo IS NULL')
            ->orderBy('fecha_objetivo')
            ->orderBy('created_at')
            ->get()
            ->map(fn (MetaAhorro $meta): array => $this->representar($meta))
            ->values());
    }

    public function store(StoreMetaAhorroRequest $request): JsonResponse
    {
        $meta = $request->user()->metasAhorro()->create([
            ...$request->validated(),
            'monto_ahorrado' => $request->validated('monto_ahorrado', '0.00'),
        ]);

        return response()->json($this->representar($meta), 201);
    }

    public function update(UpdateMetaAhorroRequest $request, int $metaAhorro): JsonResponse
    {
        $meta = $request->user()->metasAhorro()->whereKey($metaAhorro)->firstOrFail();
        $meta->update($request->validated());

        return response()->json($this->representar($meta->refresh()));
    }

    public function destroy(Request $request, int $metaAhorro): Response
    {
        $request->user()->metasAhorro()->whereKey($metaAhorro)->firstOrFail()->delete();

        return response()->noContent();
    }

    private function representar(MetaAhorro $meta): array
    {
        $objetivo = $this->aCentavos($meta->monto_objetivo);
        $ahorrado = $this->aCentavos($meta->monto_ahorrado);

        return [
            'id' => $meta->getKey(),
            'nombre' => $meta->nombre,
            'monto_objetivo' => $meta->monto_objetivo,
            'monto_ahorrado' => $meta->monto_ahorrado,
            'fecha_objetivo' => $meta->fecha_objetivo?->format('Y-m-d'),
            'porcentaje' => $objetivo > 0
                ? $this->formatearCentavos(intdiv(($ahorrado * 10000) + intdiv($objetivo, 2), $objetivo))
                : '0.00',
        ];
    }

    private function aCentavos(mixed $monto): int
    {
        preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', (string) $monto, $partes);

        return ((int) ($partes[1] ?? 0) * 100) + (int) str_pad($partes[2] ?? '', 2, '0');
    }

    private function formatearCentavos(int $centavos): string
    {
        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }
}
