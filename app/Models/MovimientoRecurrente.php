<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'tipo',
    'categoria_id',
    'subcategoria_id',
    'concepto',
    'monto',
    'proxima_fecha',
    'dia_programado',
    'activo',
    'notas',
])]
class MovimientoRecurrente extends Model
{
    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'proxima_fecha' => 'date',
            'dia_programado' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(Subcategoria::class);
    }
}
