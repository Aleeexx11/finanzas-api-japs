<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Ingreso;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingreso>
 */
class IngresoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Ingreso>
     */
    protected $model = Ingreso::class;

    /**
     * Define the model's default state.
     *
     * The catalog seeder must run before using this factory so that an
     * income category is available.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'categoria_id' => Categoria::query()
                ->whereNull('user_id')
                ->where('tipo', 'ingreso')
                ->inRandomOrder()
                ->value('id'),
            'fecha' => fake()->dateTimeBetween('2026-01-01', '2026-07-31')
                ->format('Y-m-d'),
            'fuente' => fake()->randomElement([
                'Empleo de medio tiempo',
                'Freelance / Proyecto',
                'Apoyo familiar',
                'Beca universitaria',
            ]),
            'monto' => fake()->randomElement([
                '1500.00',
                '1800.00',
                '2200.00',
                '2500.00',
            ]),
            'notas' => fake()->optional()->sentence(),
        ];
    }
}
