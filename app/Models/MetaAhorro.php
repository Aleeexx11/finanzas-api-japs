<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'nombre',
    'monto_objetivo',
    'monto_ahorrado',
    'fecha_objetivo',
])]
class MetaAhorro extends Model
{
    protected function casts(): array
    {
        return [
            'monto_objetivo' => 'decimal:2',
            'monto_ahorrado' => 'decimal:2',
            'fecha_objetivo' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
