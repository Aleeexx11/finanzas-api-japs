<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'categoria_id',
    'periodo_inicio',
    'monto_limite',
    'alerta_porcentaje',
    'alerta_activa',
])]
class Presupuesto extends Model
{
    protected function casts(): array
    {
        return [
            'periodo_inicio' => 'date',
            'monto_limite' => 'decimal:2',
            'alerta_porcentaje' => 'integer',
            'alerta_activa' => 'boolean',
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
}
