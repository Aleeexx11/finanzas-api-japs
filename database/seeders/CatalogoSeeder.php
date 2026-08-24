<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Subcategoria;
use Illuminate\Database\Seeder;

class CatalogoSeeder extends Seeder
{
    /**
     * Seed the system categories and their subcategories.
     */
    public function run(): void
    {
        $categoriasIngreso = [
            'Empleo',
            'Freelance / Proyecto',
            'Negocio Propio',
            'Inversión / Dividendos',
            'Bono / Extra',
            'Otro Ingreso',
        ];

        $categoriasEgreso = [
            'Vivienda' => ['Alquiler', 'Agua', 'Luz', 'Internet'],
            'Educación' => ['Universidad', 'Cursos', 'Libros'],
            'Alimentación' => ['Supermercado', 'Restaurante', 'Almuerzo'],
            'Transporte' => ['Gasolina', 'Bus', 'Taxi / Uber', 'Parqueo'],
            'Salud' => ['Consulta', 'Medicamentos', 'Laboratorio'],
            'Ocio / Entretenimiento' => ['Suscripciones', 'Cine', 'Salidas'],
            'Deporte' => ['Gimnasio', 'Equipo deportivo'],
            'Imprevistos' => ['Emergencias', 'Reparaciones'],
            'Otro Egreso' => [],
        ];

        foreach ($categoriasIngreso as $nombre) {
            $this->crearCategoriaSistema($nombre, 'ingreso');
        }

        foreach ($categoriasEgreso as $nombre => $subcategorias) {
            $categoria = $this->crearCategoriaSistema($nombre, 'egreso');

            foreach ($subcategorias as $nombreSubcategoria) {
                Subcategoria::firstOrCreate([
                    'categoria_id' => $categoria->id,
                    'nombre' => $nombreSubcategoria,
                ]);
            }
        }
    }

    /**
     * Find or create a system category without associating it with a user.
     */
    private function crearCategoriaSistema(string $nombre, string $tipo): Categoria
    {
        return Categoria::firstOrCreate([
            'user_id' => null,
            'nombre' => $nombre,
            'tipo' => $tipo,
        ]);
    }
}
