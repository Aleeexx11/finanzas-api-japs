<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\User;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardResumenTest extends TestCase
{
    use RefreshDatabase;

    public function test_resumen_calcula_el_mes_y_el_acumulado_con_una_consulta(): void
    {
        $this->seed(CatalogoSeeder::class);

        $usuario = User::factory()->create();
        $otroUsuario = User::factory()->create();
        $categoriaIngreso = Categoria::query()->where('tipo', 'ingreso')->firstOrFail();
        $categoriaEgreso = Categoria::query()->where('tipo', 'egreso')->firstOrFail();

        $this->crearIngreso($usuario, $categoriaIngreso, '2026-01-10', '100.10');
        $this->crearIngreso($usuario, $categoriaIngreso, '2026-03-10', '200.20');
        $this->crearIngreso($usuario, $categoriaIngreso, '2026-04-10', '999.99');
        $this->crearIngreso($usuario, $categoriaIngreso, '2025-03-10', '777.77');
        $this->crearIngreso($otroUsuario, $categoriaIngreso, '2026-03-10', '888.88');

        $this->crearEgreso($usuario, $categoriaEgreso, '2026-02-10', '30.05');
        $this->crearEgreso($usuario, $categoriaEgreso, '2026-03-10', '50.15');
        $this->crearEgreso($usuario, $categoriaEgreso, '2026-04-10', '444.44');
        $this->crearEgreso($otroUsuario, $categoriaEgreso, '2026-03-10', '333.33');

        Sanctum::actingAs($usuario);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->getJson('/api/dashboard/resumen?anio=2026&mes=3');

        $response->assertOk()->assertExactJson([
            'ingresos_mes' => '200.20',
            'egresos_mes' => '50.15',
            'balance_mes' => '150.05',
            'ingresos_acumulados' => '300.30',
            'egresos_acumulados' => '80.20',
            'balance_acumulado' => '220.10',
            'porcentaje_gastado' => '25.05',
        ]);

        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_resumen_devuelve_ceros_si_no_hay_movimientos(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/dashboard/resumen?anio=2026&mes=6')
            ->assertOk()
            ->assertExactJson([
                'ingresos_mes' => '0.00',
                'egresos_mes' => '0.00',
                'balance_mes' => '0.00',
                'ingresos_acumulados' => '0.00',
                'egresos_acumulados' => '0.00',
                'balance_acumulado' => '0.00',
                'porcentaje_gastado' => '0.00',
            ]);
    }

    public function test_resumen_requiere_autenticacion_y_parametros_validos(): void
    {
        $this->getJson('/api/dashboard/resumen?anio=2026&mes=3')
            ->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/dashboard/resumen?anio=2026&mes=13')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mes']);
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
