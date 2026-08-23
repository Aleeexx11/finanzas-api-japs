<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'categoria_id',
    'nombre',
])]
class Subcategoria extends Model
{
    use HasFactory;

    /**
     * The category that owns the subcategory.
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * The expenses assigned to the subcategory.
     */
    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class);
    }
}
