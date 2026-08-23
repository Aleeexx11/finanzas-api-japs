<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'nombre',
    'tipo',
])]
class Categoria extends Model
{
    use HasFactory;

    /**
     * The user who owns the category. Null means it is a system category.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The subcategories that belong to the category.
     */
    public function subcategorias(): HasMany
    {
        return $this->hasMany(Subcategoria::class);
    }

    /**
     * The incomes assigned to the category.
     */
    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class);
    }

    /**
     * The expenses assigned to the category.
     */
    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class);
    }
}
