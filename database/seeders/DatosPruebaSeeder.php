<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\Subcategoria;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class DatosPruebaSeeder extends Seeder
{
    /**
     * Seed deterministic dashboard data for two demo users.
     *
     * June is intentionally omitted so it remains a completely empty month.
     */
    public function run(): void
    {
        $this->call(CatalogoSeeder::class);

        $mesesConDatos = [1, 2, 3, 4, 5, 7];
        $usuarios = [
            [
                'email' => 'test@example.com',
                'nombre' => 'Estudiante Demo 1',
                'categoria_ingreso' => 'Empleo',
                'ingresos_centavos' => [
                    250000,
                    250000,
                    260000,
                    250000,
                    270000,
                    280000,
                ],
                'factor_egresos_porcentaje' => 100,
                'desfase_dias' => 0,
            ],
            [
                'email' => 'estudiante2@example.com',
                'nombre' => 'Estudiante Demo 2',
                'categoria_ingreso' => 'Freelance / Proyecto',
                'ingresos_centavos' => [
                    180000,
                    190000,
                    180000,
                    200000,
                    190000,
                    210000,
                ],
                'factor_egresos_porcentaje' => 80,
                'desfase_dias' => 1,
            ],
        ];

        foreach ($usuarios as $configuracion) {
            $usuario = User::firstOrCreate(
                ['email' => $configuracion['email']],
                [
                    'name' => $configuracion['nombre'],
                    'email_verified_at' => now(),
                    'password' => 'password',
                ],
            );

            foreach ($mesesConDatos as $indice => $mes) {
                $this->crearIngreso(
                    $usuario,
                    $mes,
                    $configuracion['categoria_ingreso'],
                    $configuracion['ingresos_centavos'][$indice],
                );

                foreach ($this->plantillasEgresos() as $plantilla) {
                    $this->crearEgreso(
                        $usuario,
                        $mes,
                        $plantilla,
                        $configuracion['factor_egresos_porcentaje'],
                        $configuracion['desfase_dias'],
                    );
                }
            }
        }
    }

    /**
     * Create or update the single monthly income for a demo user.
     */
    private function crearIngreso(
        User $usuario,
        int $mes,
        string $nombreCategoria,
        int $montoCentavos,
    ): void {
        $categoria = $this->categoriaSistema($nombreCategoria, 'ingreso');
        $fecha = CarbonImmutable::create(2026, $mes, 5)->toDateString();
        $fuente = $nombreCategoria === 'Empleo'
            ? 'Empleo de medio tiempo'
            : 'Proyecto freelance universitario';

        $valores = Ingreso::factory()->make([
            'user_id' => $usuario->id,
            'categoria_id' => $categoria->id,
            'fecha' => $fecha,
            'fuente' => $fuente,
            'monto' => $this->montoDecimal($montoCentavos),
            'notas' => 'Ingreso de prueba para el dashboard.',
        ])->getAttributes();

        Ingreso::query()->updateOrCreate(
            [
                'user_id' => $usuario->id,
                'fecha' => $fecha,
                'fuente' => $fuente,
            ],
            Arr::except($valores, ['id', 'created_at', 'updated_at']),
        );
    }

    /**
     * Create or update one deterministic expense per system expense category.
     */
    private function crearEgreso(
        User $usuario,
        int $mes,
        array $plantilla,
        int $factorPorcentaje,
        int $desfaseDias,
    ): void {
        $categoria = $this->categoriaSistema($plantilla['categoria'], 'egreso');
        $subcategoriaId = null;

        if ($plantilla['subcategoria'] !== null) {
            $subcategoriaId = Subcategoria::query()
                ->where('categoria_id', $categoria->id)
                ->where('nombre', $plantilla['subcategoria'])
                ->firstOrFail()
                ->id;
        }

        $fecha = CarbonImmutable::create(
            2026,
            $mes,
            $plantilla['dia'] + $desfaseDias,
        )->toDateString();
        $montoCentavos = intdiv(
            $plantilla['monto_centavos'] * $factorPorcentaje,
            100,
        );

        $valores = Egreso::factory()->make([
            'user_id' => $usuario->id,
            'categoria_id' => $categoria->id,
            'subcategoria_id' => $subcategoriaId,
            'fecha' => $fecha,
            'descripcion' => $plantilla['descripcion'],
            'monto' => $this->montoDecimal($montoCentavos),
            'notas' => 'Egreso de prueba para el dashboard.',
        ])->getAttributes();

        Egreso::query()->updateOrCreate(
            [
                'user_id' => $usuario->id,
                'fecha' => $fecha,
                'descripcion' => $plantilla['descripcion'],
            ],
            Arr::except($valores, ['id', 'created_at', 'updated_at']),
        );
    }

    /**
     * Return the requested system category or fail with a useful error.
     */
    private function categoriaSistema(string $nombre, string $tipo): Categoria
    {
        return Categoria::query()
            ->whereNull('user_id')
            ->where('nombre', $nombre)
            ->where('tipo', $tipo)
            ->firstOrFail();
    }

    /**
     * Keep money as a decimal string without converting it to a float.
     */
    private function montoDecimal(int $centavos): string
    {
        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }

    /**
     * Nine expenses per month: one for every system expense category.
     */
    private function plantillasEgresos(): array
    {
        return [
            [
                'categoria' => 'Vivienda',
                'subcategoria' => 'Internet',
                'dia' => 2,
                'descripcion' => 'Aporte de vivienda e internet',
                'monto_centavos' => 45000,
            ],
            [
                'categoria' => 'Educación',
                'subcategoria' => 'Libros',
                'dia' => 4,
                'descripcion' => 'Material y libros de estudio',
                'monto_centavos' => 18000,
            ],
            [
                'categoria' => 'Alimentación',
                'subcategoria' => 'Supermercado',
                'dia' => 7,
                'descripcion' => 'Compra de supermercado',
                'monto_centavos' => 35000,
            ],
            [
                'categoria' => 'Transporte',
                'subcategoria' => 'Bus',
                'dia' => 10,
                'descripcion' => 'Transporte a la universidad',
                'monto_centavos' => 15000,
            ],
            [
                'categoria' => 'Salud',
                'subcategoria' => 'Medicamentos',
                'dia' => 13,
                'descripcion' => 'Medicamentos y cuidado personal',
                'monto_centavos' => 8000,
            ],
            [
                'categoria' => 'Ocio / Entretenimiento',
                'subcategoria' => 'Suscripciones',
                'dia' => 16,
                'descripcion' => 'Suscripción de entretenimiento',
                'monto_centavos' => 12000,
            ],
            [
                'categoria' => 'Deporte',
                'subcategoria' => 'Gimnasio',
                'dia' => 20,
                'descripcion' => 'Gimnasio',
                'monto_centavos' => 9000,
            ],
            [
                'categoria' => 'Imprevistos',
                'subcategoria' => 'Reparaciones',
                'dia' => 23,
                'descripcion' => 'Reparación o imprevisto',
                'monto_centavos' => 10000,
            ],
            [
                'categoria' => 'Otro Egreso',
                'subcategoria' => null,
                'dia' => 26,
                'descripcion' => 'Gasto personal varios',
                'monto_centavos' => 6000,
            ],
        ];
    }
}
