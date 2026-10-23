<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('categoria_id');
            $table->date('periodo_inicio');
            $table->decimal('monto_limite', 12, 2);
            $table->unsignedTinyInteger('alerta_porcentaje')->default(80);
            $table->boolean('alerta_activa')->default(true);
            $table->timestamps();

            $table->unique(
                ['user_id', 'categoria_id', 'periodo_inicio'],
                'presupuestos_user_categoria_periodo_unique',
            );
            $table->index(['user_id', 'periodo_inicio'], 'presupuestos_user_periodo_index');
            $table->foreign('user_id', 'fk_presupuestos_user')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('categoria_id', 'fk_presupuestos_categoria')
                ->references('id')->on('categorias')->restrictOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE presupuestos ADD CONSTRAINT chk_presupuestos_monto_limite CHECK (monto_limite > 0)');
            DB::statement('ALTER TABLE presupuestos ADD CONSTRAINT chk_presupuestos_alerta_porcentaje CHECK (alerta_porcentaje BETWEEN 1 AND 100)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
