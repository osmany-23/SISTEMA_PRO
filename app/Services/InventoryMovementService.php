<?php

namespace App\Services;

use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class InventoryMovementService
{
    public function record(array $payload, ?Model $source = null): ?InventoryMovement
    {
        if (! Schema::hasTable('inventory_movements')) {
            return null;
        }

        $movementPayload = [
            'tipo_documento' => $payload['tipo_documento'],
            'referencia' => $payload['referencia'] ?? null,
            'product_id' => (int) $payload['product_id'],
            'warehouse_id' => $payload['warehouse_id'] ?? null,
            'user_id' => $payload['user_id'] ?? auth()->id(),
            'movimiento' => $payload['movimiento'],
            'cantidad' => round((float) $payload['cantidad'], 2),
            'stock_anterior' => round((float) ($payload['stock_anterior'] ?? 0), 2),
            'stock_nuevo' => round((float) ($payload['stock_nuevo'] ?? 0), 2),
            'motivo' => $payload['motivo'] ?? null,
            'fecha' => $payload['fecha'] ?? now(),
        ];

        if ($source) {
            $movementPayload['source_type'] = $source::class;
            $movementPayload['source_id'] = $source->getKey();
        }

        return InventoryMovement::create($movementPayload);
    }
}
