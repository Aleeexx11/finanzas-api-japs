<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Egreso;
use App\Models\Subcategoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Egreso>
 */
class EgresoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Egreso>
     */
    protected $model = Egreso::class;

    /**
     * Define the model's default state.
     *
     * The catalog seeder must run before using this factory so that an
     * expense category is available.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categoriaId = Categoria::query()
            ->whereNull('user_id')
            ->where('tipo', 'egreso')
            ->inRandomOrder()
            ->value('id');

        return [
            'user_id' => User::factory(),
            'categoria_id' => $categoriaId,
            'subcategoria_id' => $categoriaId
                ? Subcategoria::query()
                    ->where('categoria_id', $categoriaId)
                    ->inRandomOrder()
                    ->value('id')
                : null,
            'fecha' => fake()->dateTimeBetween('2026-01-01', '2026-07-31')
                ->format('Y-m-d'),
            'descripcion' => fake()->randomElement([
                'Compra de supermercado',
                'Transporte a la universidad',
                'Pago de internet',
                'Almuerzo',
                'Material de estudio',
            ]),
            'monto' => fake()->randomElement([
                '35.00',
                '75.00',
                '120.00',
                '250.00',
                '450.00',
            ]),
            'notas' => fake()->optional()->sentence(),
        ];
    }
}
