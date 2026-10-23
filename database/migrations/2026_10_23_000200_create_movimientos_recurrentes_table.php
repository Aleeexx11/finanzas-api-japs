<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_recurrentes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->foreignId('categoria_id');
            $table->foreignId('subcategoria_id')->nullable();
            $table->string('concepto', 150);
            $table->decimal('monto', 12, 2);
            $table->date('proxima_fecha');
            $table->unsignedTinyInteger('dia_programado');
            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'activo', 'proxima_fecha'], 'recurrentes_user_activo_fecha_index');
            $table->foreign('user_id', 'fk_recurrentes_user')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('categoria_id', 'fk_recurrentes_categoria')
                ->references('id')->on('categorias')->restrictOnDelete();
            $table->foreign('subcategoria_id', 'fk_recurrentes_subcategoria')
                ->references('id')->on('subcategorias')->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE movimientos_recurrentes ADD CONSTRAINT chk_recurrentes_monto CHECK (monto >= 0)');
            DB::statement('ALTER TABLE movimientos_recurrentes ADD CONSTRAINT chk_recurrentes_dia_programado CHECK (dia_programado BETWEEN 1 AND 31)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_recurrentes');
    }
};
