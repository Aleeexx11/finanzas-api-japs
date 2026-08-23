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
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('nombre', 80);
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->timestamps();

            $table->index('user_id', 'categorias_user_id_index');
            $table->index('tipo', 'categorias_tipo_index');
            $table->unique(
                ['user_id', 'nombre', 'tipo'],
                'categorias_user_nombre_tipo_unique'
            );

            $table->foreign('user_id', 'fk_categorias_user')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
