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
        Schema::create('ingresos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('categoria_id');
            $table->date('fecha');
            $table->string('fuente', 150);
            $table->decimal('monto', 12, 2);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'fecha'], 'ingresos_user_fecha_index');
            $table->index('categoria_id', 'ingresos_categoria_id_index');

            $table->foreign('user_id', 'fk_ingresos_user')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('categoria_id', 'fk_ingresos_categoria')
                ->references('id')
                ->on('categorias')
                ->restrictOnDelete();
        });

        DB::statement(
            'ALTER TABLE ingresos ADD CONSTRAINT chk_ingresos_monto CHECK (monto >= 0)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingresos');
    }
};
