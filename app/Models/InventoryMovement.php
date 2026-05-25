<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_documento',
        'referencia',
        'product_id',
        'warehouse_id',
        'user_id',
        'movimiento',
        'cantidad',
        'stock_anterior',
        'stock_nuevo',
        'motivo',
        'source_id',
        'source_type',
        'fecha',
    ];

    protected $casts = [
        'cantidad' => 'double',
        'stock_anterior' => 'double',
        'stock_nuevo' => 'double',
        'fecha' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
