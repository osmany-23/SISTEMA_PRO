<?php

namespace App\Repositories;

use App\Models\Adjustment;
use App\Models\AdjustmentItem;
use App\Models\ManageStock;
use App\Services\InventoryMovementService;
use App\Services\ProductBatchService;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class SaleRepository
 */
class AdjustmentRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'date',
        'reference_code',
        'warehouse_id',
        'total_products',
        'created_at',
    ];

    /**
     * @var string[]
     */
    protected $allowedFields = [
        'date',
    ];

    /**
     * Return searchable fields
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Adjustment::class;
    }

    public function storeAdjustment($input): Adjustment
    {
        try {
            DB::beginTransaction();

            $this->assertNoTrackedBatchProducts(
                collect($input['adjustment_items'] ?? [])->pluck('product_id')->filter()->all(),
                'Los productos con control por lote deben ajustarse desde la gestion de lotes.'
            );
            $input['total_products'] = count($input['adjustment_items']);
            $input['date'] = $input['date'] ?? date('Y/m/d');
            $input['user_id'] = $this->resolveAuthenticatedUserId();
            $adjustmentInputArray = Arr::only($input, $this->availableAdjustmentColumns([
                'date', 'warehouse_id', 'total_products', 'descripcion', 'user_id',
            ]));
            $adjustment = Adjustment::create($adjustmentInputArray);
            $reference_code = 'AD_111'.$adjustment->id;
            $adjustment->update(['reference_code' => $reference_code]);

            $adjustment = $this->storeAdjustmentItems($adjustment, $input);

            DB::commit();

            return $adjustment->load('warehouse', 'user', 'adjustmentItems.product');
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function storeAdjustmentItems($adjustment, $input)
    {
        foreach ($input['adjustment_items'] as $adjustmentItem) {
            $adjustmentItem['adjustment_id'] = $adjustment->id;
            $adjustmentItem = $this->normalizeAdjustmentItem($adjustmentItem);
            $createdItem = AdjustmentItem::create($adjustmentItem);
            $this->applyStockMovement($adjustment, $createdItem);
            continue;

            $product = ManageStock::whereWarehouseId($adjustment->warehouse_id)->whereProductId($adjustmentItem['product_id'])->first();
            if (! empty($product)) {
                if ($adjustmentItem['method_type'] == AdjustmentItem::METHOD_ADDITION) {
                    $totalQuantity = $product->quantity + $adjustmentItem['quantity'];
                    $product->update([
                        'quantity' => $totalQuantity,
                    ]);
                } else {
                    $totalQuantity = $product->quantity - $adjustmentItem['quantity'];
                    if ($totalQuantity < 0) {
                        throw new UnprocessableEntityHttpException('Quantity exceeds quantity available in stock.');
                    }
                    $product->update([
                        'quantity' => $totalQuantity,
                    ]);
                }
            } else {
                if ($adjustmentItem['method_type'] == AdjustmentItem::METHOD_ADDITION) {
                    ManageStock::create([
                        'warehouse_id' => $adjustment->warehouse_id,
                        'product_id' => $adjustmentItem['product_id'],
                        'quantity' => $adjustmentItem['quantity'],
                    ]);
                }
            }
        }

        return $adjustment;
    }

    public function updateAdjustment($input, $id)
    {
        try {
            DB::beginTransaction();

            $adjustment = Adjustment::findOrFail($id);
            $this->assertNoTrackedBatchProducts(
                collect($input['adjustment_items'] ?? [])->pluck('product_id')->filter()->all(),
                'Los productos con control por lote deben ajustarse desde la gestion de lotes.'
            );

            $input['total_products'] = count($input['adjustment_items']);
            $input['date'] = $input['date'] ?? date('Y/m/d');
            $input['user_id'] = $adjustment->user_id ?: $this->resolveAuthenticatedUserId();
            $adjustmentInputArray = Arr::only($input, $this->availableAdjustmentColumns([
                'date', 'warehouse_id', 'total_products', 'descripcion', 'user_id',
            ]));
            $adjustment->update($adjustmentInputArray);

            $adjustment = $this->updateAdjustmentItems($adjustment, $input);

            DB::commit();

            return $adjustment->load('warehouse', 'user', 'adjustmentItems.product');
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function updateAdjustmentItems($adjustment, $input)
    {
        $oldItems = AdjustmentItem::whereAdjustmentId($adjustment->id)->lockForUpdate()->get();
        foreach ($oldItems as $oldItem) {
            $this->applyStockMovement($adjustment, $oldItem, true);
        }

        AdjustmentItem::whereAdjustmentId($adjustment->id)->delete();
        $this->storeAdjustmentItems($adjustment, $input);

        return $adjustment;

        $adjustmentItmOldIds = AdjustmentItem::whereAdjustmentId($adjustment->id)->pluck('id')->toArray();
        $adjustmentItemIds = [];

        foreach ($input['adjustment_items'] as $key => $adjustmentItem) {
            $adjustmentItemIds[$key] = $adjustmentItem['adjustment_item_id'];

            $product = ManageStock::whereWarehouseId($adjustment->warehouse_id)->whereProductId($adjustmentItem['product_id'])->first();

            if (is_null($adjustmentItem['adjustment_item_id'])) {
                $adjustmentItem['adjustment_id'] = $adjustment->id;

                AdjustmentItem::Create($adjustmentItem);

                if (! empty($product)) {
                    if ($adjustmentItem['method_type'] == AdjustmentItem::METHOD_ADDITION) {
                        $totalQuantity = $product->quantity + $adjustmentItem['quantity'];
                        $product->update([
                            'quantity' => $totalQuantity,
                        ]);
                    } else {
                        $totalQuantity = $product->quantity - $adjustmentItem['quantity'];
                        if ($totalQuantity < 0) {
                            throw new UnprocessableEntityHttpException('Quantity exceeds quantity available in stock.');
                        }
                        $product->update([
                            'quantity' => $totalQuantity,
                        ]);
                    }
                } else {
                    if ($adjustmentItem['method_type'] == AdjustmentItem::METHOD_ADDITION) {
                        ManageStock::create([
                            'warehouse_id' => $adjustment->warehouse_id,
                            'product_id' => $adjustmentItem['product_id'],
                            'quantity' => $adjustmentItem['quantity'],
                        ]);
                    }
                }
            } else {
                $exitAdjustmentItem = AdjustmentItem::whereId($adjustmentItem['adjustment_item_id'])->firstOrFail();

                if ($exitAdjustmentItem['method_type'] == AdjustmentItem::METHOD_ADDITION) {
                    $existQuantity = $product->quantity - $exitAdjustmentItem->quantity;
                } else {
                    $existQuantity = $product->quantity + $exitAdjustmentItem->quantity;
                }

                if ($adjustmentItem['method_type'] == AdjustmentItem::METHOD_ADDITION) {
                    $totalQuantity = $existQuantity + $adjustmentItem['quantity'];
                    $product->update([
                        'quantity' => $totalQuantity,
                    ]);
                } else {
                    $totalQuantity = $existQuantity - $adjustmentItem['quantity'];
                    if ($totalQuantity < 0) {
                        throw new UnprocessableEntityHttpException('Quantity exceeds quantity available in stock.');
                    }
                    $product->update([
                        'quantity' => $totalQuantity,
                    ]);
                }

                $exitAdjustmentItem->update([
                    'quantity' => $adjustmentItem['quantity'],
                    'method_type' => $adjustmentItem['method_type'],
                ]);
            }
        }

        $removeItemIds = array_diff($adjustmentItmOldIds, $adjustmentItemIds);

        if (! empty(array_values($removeItemIds))) {
            foreach ($removeItemIds as $removeItemId) {
                $oldItem = AdjustmentItem::whereId($removeItemId)->firstOrFail();
                $existProductStock = ManageStock::whereWarehouseId($adjustment->warehouse_id)->whereProductId($oldItem->product_id)->first();

                if ($oldItem->method_type == AdjustmentItem::METHOD_ADDITION) {
                    $totalQuantity = $existProductStock->quantity - $oldItem['quantity'];
                } else {
                    $totalQuantity = $existProductStock->quantity + $oldItem['quantity'];
                }

                $existProductStock->update([
                    'quantity' => $totalQuantity,
                ]);
            }
            AdjustmentItem::whereIn('id', array_values($removeItemIds))->delete();
        }

        return $adjustment;
    }

    private function normalizeAdjustmentItem(array $adjustmentItem): array
    {
        $movement = $adjustmentItem['tipo_movimiento'] ?? null;
        if (! in_array($movement, [AdjustmentItem::MOVEMENT_ENTRY, AdjustmentItem::MOVEMENT_EXIT], true)) {
            $movement = (int) ($adjustmentItem['method_type'] ?? 0) === AdjustmentItem::METHOD_SUBTRACTION
                ? AdjustmentItem::MOVEMENT_EXIT
                : AdjustmentItem::MOVEMENT_ENTRY;
        }

        $adjustmentItem['tipo_movimiento'] = $movement;
        $adjustmentItem['method_type'] = $movement === AdjustmentItem::MOVEMENT_ENTRY
            ? AdjustmentItem::METHOD_ADDITION
            : AdjustmentItem::METHOD_SUBTRACTION;
        $adjustmentItem['quantity'] = round((float) ($adjustmentItem['quantity'] ?? 0), 2);

        if ($adjustmentItem['quantity'] <= 0) {
            throw new UnprocessableEntityHttpException('La cantidad del ajuste debe ser mayor a cero.');
        }

        return Arr::only($adjustmentItem, $this->availableAdjustmentItemColumns([
            'adjustment_id', 'product_id', 'method_type', 'tipo_movimiento', 'quantity',
        ]));
    }

    private function applyStockMovement(Adjustment $adjustment, AdjustmentItem $item, bool $reverse = false): void
    {
        $movement = $item->tipo_movimiento ?: $item->movementFromMethod();
        if ($reverse) {
            $movement = $movement === AdjustmentItem::MOVEMENT_ENTRY
                ? AdjustmentItem::MOVEMENT_EXIT
                : AdjustmentItem::MOVEMENT_ENTRY;
        }

        $stock = ManageStock::whereWarehouseId($adjustment->warehouse_id)
            ->whereProductId($item->product_id)
            ->lockForUpdate()
            ->first();
        $previousStock = round((float) optional($stock)->quantity, 2);

        if (! $stock && $movement === AdjustmentItem::MOVEMENT_EXIT) {
            throw new UnprocessableEntityHttpException('Quantity exceeds quantity available in stock.');
        }

        if (! $stock) {
            $stock = ManageStock::create([
                'warehouse_id' => $adjustment->warehouse_id,
                'product_id' => $item->product_id,
                'quantity' => 0,
            ]);
        }

        $newStock = $movement === AdjustmentItem::MOVEMENT_ENTRY
            ? $previousStock + (float) $item->quantity
            : $previousStock - (float) $item->quantity;

        if ($newStock < 0) {
            throw new UnprocessableEntityHttpException('Quantity exceeds quantity available in stock.');
        }

        $stock->update(['quantity' => $newStock]);

        app(InventoryMovementService::class)->record([
            'tipo_documento' => $reverse ? 'AJUSTE_REVERSION' : 'AJUSTE',
            'referencia' => $adjustment->reference_code,
            'product_id' => $item->product_id,
            'warehouse_id' => $adjustment->warehouse_id,
            'user_id' => $adjustment->user_id ?: $this->resolveAuthenticatedUserId(),
            'movimiento' => $movement,
            'cantidad' => $item->quantity,
            'stock_anterior' => $previousStock,
            'stock_nuevo' => $newStock,
            'motivo' => $reverse
                ? 'Reversion de ajuste: '.($adjustment->descripcion ?: $adjustment->reference_code)
                : ($adjustment->descripcion ?: 'Ajuste de inventario'),
            'fecha' => now(),
        ], $item);
    }

    private function availableAdjustmentColumns(array $columns): array
    {
        return array_values(array_filter($columns, fn ($column) => Schema::hasColumn('adjustments', $column)));
    }

    private function availableAdjustmentItemColumns(array $columns): array
    {
        return array_values(array_filter($columns, fn ($column) => Schema::hasColumn('adjustment_items', $column)));
    }

    private function resolveAuthenticatedUserId(): ?int
    {
        $userId = optional(request()->user())->id
            ?: optional(auth('sanctum')->user())->id
            ?: Auth::id()
            ?: auth()->id();

        if ($userId) {
            return (int) $userId;
        }

        return null;
    }

    private function assertNoTrackedBatchProducts(array $productIds, string $message): void
    {
        if (! app(ProductBatchService::class)->batchTablesExist()) {
            return;
        }

        app(ProductBatchService::class)->assertTrackedProductsNotPresent($productIds, $message);
    }
}
