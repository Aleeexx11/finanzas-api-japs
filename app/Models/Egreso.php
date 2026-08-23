<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'categoria_id',
    'subcategoria_id',
    'fecha',
    'descripcion',
    'monto',
    'notas',
])]
class Egreso extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    /**
     * The user who owns the expense.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The category assigned to the expense.
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * The optional subcategory assigned to the expense.
     */
    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(Subcategoria::class);
    }

    /**
     * Scope the query to a calendar month using the fecha column.
     */
    public function scopeDelMes(Builder $query, int $anio, int $mes): Builder
    {
        return $query
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes);
    }
}
