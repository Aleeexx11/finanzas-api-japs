<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\User;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardResumenAnualTest extends TestCase
{
    use RefreshDatabase;

    public function test_devuelve_los_doce_meses_incluyendo_los_meses_sin_datos(): void
    {
        $this->seed(CatalogoSeeder::class);

        $usuario = User::factory()->create();
        $otroUsuario = User::factory()->create();
        $categoriaIngreso = Categoria::query()->where('tipo', 'ingreso')->firstOrFail();
        $categoriaEgreso = Categoria::query()->where('tipo', 'egreso')->firstOrFail();

        $this->crearIngreso($usuario, $categoriaIngreso, '2026-01-10', '100.10');
        $this->crearIngreso($usuario, $categoriaIngreso, '2026-03-10', '200.20');
        $this->crearEgreso($usuario, $categoriaEgreso, '2026-01-15', '30.05');
        $this->crearEgreso($usuario, $categoriaEgreso, '2026-03-15', '50.15');

        $this->crearIngreso($usuario, $categoriaIngreso, '2025-01-10', '999.99');
        $this->crearIngreso($otroUsuario, $categoriaIngreso, '2026-01-10', '888.88');
        $this->crearEgreso($otroUsuario, $categoriaEgreso, '2026-03-15', '333.33');

        Sanctum::actingAs($usuario);

        $response = $this->getJson('/api/dashboard/resumen-anual?anio=2026');

        $response->assertOk()->assertExactJson([
            ['mes' => 1, 'ingresos' => '100.10', 'egresos' => '30.05', 'balance' => '70.05'],
            ['mes' => 2, 'ingresos' => '0.00', 'egresos' => '0.00', 'balance' => '0.00'],
            ['mes' => 3, 'ingresos' => '200.20', 'egresos' => '50.15', 'balance' => '150.05'],
            ['mes' => 4, 'ingresos' => '0.00', 'egresos' => '0.00', 'balance' => '0.00'],
            ['mes' => 5, 'ingresos' => '0.00', 'egresos' => '0.00', 'balance' => '0.00'],
            ['mes' => 6, 'ingresos' => '0.00', 'egresos' => '0.00', 'balance' => '0.00'],
            ['mes' => 7, 'ingresos' => '0.00', 'egresos' => '0.00', 'balance' => '0.00'],
            ['mes' => 8, 'ingresos' => '0.00', 'egresos' => '0.00', 'balance' => '0.00'],
            ['mes' => 9, 'ingresos' => '0.00', 'egresos' => '0.00', 'balance' => '0.00'],
            ['mes' => 10, 'ingresos' => '0.00', 'egresos' => '0.00', 'balance' => '0.00'],
            ['mes' => 11, 'ingresos' => '0.00', 'egresos' => '0.00', 'balance' => '0.00'],
            ['mes' => 12, 'ingresos' => '0.00', 'egresos' => '0.00', 'balance' => '0.00'],
        ]);
    }

    public function test_requiere_autenticacion_y_un_anio_valido(): void
    {
        $this->getJson('/api/dashboard/resumen-anual?anio=2026')
            ->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/dashboard/resumen-anual')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['anio']);

        $this->getJson('/api/dashboard/resumen-anual?anio=2101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['anio']);
    }

    private function crearIngreso(
        User $usuario,
        Categoria $categoria,
        string $fecha,
        string $monto,
    ): void {
        Ingreso::query()->create([
            'user_id' => $usuario->id,
            'categoria_id' => $categoria->id,
            'fecha' => $fecha,
            'fuente' => 'Prueba',
            'monto' => $monto,
        ]);
    }

    private function crearEgreso(
        User $usuario,
        Categoria $categoria,
        string $fecha,
        string $monto,
    ): void {
        Egreso::query()->create([
            'user_id' => $usuario->id,
            'categoria_id' => $categoria->id,
            'fecha' => $fecha,
            'descripcion' => 'Prueba',
            'monto' => $monto,
        ]);
    }
}
