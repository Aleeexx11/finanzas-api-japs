<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('egresos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('categoria_id');
            $table->foreignId('subcategoria_id')->nullable();
            $table->date('fecha');
            $table->string('descripcion', 150);
            $table->decimal('monto', 12, 2);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'fecha'], 'egresos_user_fecha_index');
            $table->index('categoria_id', 'egresos_categoria_id_index');
            $table->index('subcategoria_id', 'egresos_subcategoria_id_index');

            $table->foreign('user_id', 'fk_egresos_user')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('categoria_id', 'fk_egresos_categoria')
                ->references('id')
                ->on('categorias')
                ->restrictOnDelete();

            $table->foreign('subcategoria_id', 'fk_egresos_subcategoria')
                ->references('id')
                ->on('subcategorias')
                ->nullOnDelete();

        });

        DB::statement(
            'ALTER TABLE egresos ADD CONSTRAINT chk_egresos_monto CHECK (monto >= 0)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('egresos');
    }
};
