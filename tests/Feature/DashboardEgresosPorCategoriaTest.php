<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Egreso;
use App\Models\User;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardEgresosPorCategoriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_agrupa_los_egresos_del_usuario_y_mes_por_categoria(): void
    {
        $this->seed(CatalogoSeeder::class);

        $usuario = User::factory()->create();
        $otroUsuario = User::factory()->create();
        $categorias = Categoria::query()
            ->where('tipo', 'egreso')
            ->orderBy('id')
            ->take(3)
            ->get();

        $this->crearEgreso($usuario, $categorias[0], '2026-03-01', '100.10');
        $this->crearEgreso($usuario, $categorias[0], '2026-03-31', '25.15');
        $this->crearEgreso($usuario, $categorias[1], '2026-03-15', '80.20');

        $this->crearEgreso($usuario, $categorias[0], '2026-04-01', '999.99');
        $this->crearEgreso($usuario, $categorias[1], '2025-03-15', '888.88');
        $this->crearEgreso($otroUsuario, $categorias[1], '2026-03-15', '777.77');

        Sanctum::actingAs($usuario);

        $this->getJson('/api/dashboard/egresos-por-categoria?anio=2026&mes=3')
            ->assertOk()
            ->assertExactJson([
                [
                    'id' => $categorias[0]->id,
                    'nombre' => $categorias[0]->nombre,
                    'total' => '125.25',
                ],
                [
                    'id' => $categorias[1]->id,
                    'nombre' => $categorias[1]->nombre,
                    'total' => '80.20',
                ],
            ]);
    }

    public function test_devuelve_una_lista_vacia_si_no_hay_egresos_en_el_mes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/dashboard/egresos-por-categoria?anio=2026&mes=3')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_requiere_autenticacion_y_parametros_validos(): void
    {
        $this->getJson('/api/dashboard/egresos-por-categoria?anio=2026&mes=3')
            ->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/dashboard/egresos-por-categoria?anio=2026&mes=13')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mes']);
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
