<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subcategorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id');
            $table->string('nombre', 80);
            $table->timestamps();

            $table->index('categoria_id', 'subcategorias_categoria_id_index');
            $table->unique(
                ['categoria_id', 'nombre'],
                'subcategorias_categoria_nombre_unique'
            );

            $table->foreign('categoria_id', 'fk_subcategorias_categoria')
                ->references('id')
                ->on('categorias')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subcategorias');
    }
};
