<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metas_ahorro', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('nombre', 120);
            $table->decimal('monto_objetivo', 12, 2);
            $table->decimal('monto_ahorrado', 12, 2)->default(0);
            $table->date('fecha_objetivo')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'fecha_objetivo'], 'metas_user_fecha_objetivo_index');
            $table->foreign('user_id', 'fk_metas_ahorro_user')
                ->references('id')->on('users')->cascadeOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE metas_ahorro ADD CONSTRAINT chk_metas_objetivo CHECK (monto_objetivo > 0)');
            DB::statement('ALTER TABLE metas_ahorro ADD CONSTRAINT chk_metas_ahorrado CHECK (monto_ahorrado >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('metas_ahorro');
    }
};
