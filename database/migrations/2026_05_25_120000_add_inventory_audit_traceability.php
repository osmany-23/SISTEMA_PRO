<?php

use App\Models\AdjustmentItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('adjustments')) {
            Schema::table('adjustments', function (Blueprint $table) {
                if (! Schema::hasColumn('adjustments', 'descripcion')) {
                    $table->text('descripcion')->nullable()->after('reference_code');
                }

                if (! Schema::hasColumn('adjustments', 'user_id')) {
                    $table->foreignId('user_id')
                        ->nullable()
                        ->after('warehouse_id')
                        ->constrained('users')
                        ->nullOnDelete();
                }
            });

            if (Schema::hasColumn('adjustments', 'user_id') && Schema::hasTable('users')) {
                $fallbackUserId = DB::table('users')->orderBy('id')->value('id');

                if ($fallbackUserId) {
                    DB::table('adjustments')
                        ->whereNull('user_id')
                        ->update(['user_id' => $fallbackUserId]);
                }
            }

            if (Schema::hasColumn('adjustments', 'descripcion')) {
                DB::table('adjustments')
                    ->where(function ($query) {
                        $query->whereNull('descripcion')
                            ->orWhere('descripcion', '');
                    })
                    ->update(['descripcion' => 'Ajuste de inventario sin motivo registrado']);
            }
        }

        if (Schema::hasTable('adjustment_items')) {
            Schema::table('adjustment_items', function (Blueprint $table) {
                if (! Schema::hasColumn('adjustment_items', 'tipo_movimiento')) {
                    $table->string('tipo_movimiento', 20)->nullable()->after('method_type')->index();
                }
            });

            DB::table('adjustment_items')
                ->whereNull('tipo_movimiento')
                ->where('method_type', AdjustmentItem::METHOD_ADDITION)
                ->update(['tipo_movimiento' => 'entrada']);

            DB::table('adjustment_items')
                ->whereNull('tipo_movimiento')
                ->where('method_type', AdjustmentItem::METHOD_SUBTRACTION)
                ->update(['tipo_movimiento' => 'salida']);
        }

        foreach (['sales', 'purchases', 'credits', 'transfers', 'expenses', 'sales_return', 'purchases_return'] as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'user_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('permissions') && ! DB::table('permissions')->where('name', 'ajustes.crear')->exists()) {
            $permissionPayload = [
                'name' => 'ajustes.crear',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('permissions', 'display_name')) {
                $permissionPayload['display_name'] = 'Crear ajustes';
            }

            DB::table('permissions')->insert($permissionPayload);
        }

        if (! Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->string('tipo_documento', 50)->index();
                $table->string('referencia', 100)->nullable()->index();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('movimiento', 20)->index();
                $table->decimal('cantidad', 15, 2);
                $table->decimal('stock_anterior', 15, 2)->default(0);
                $table->decimal('stock_nuevo', 15, 2)->default(0);
                $table->text('motivo')->nullable();
                $table->nullableMorphs('source');
                $table->timestamp('fecha')->useCurrent()->index();
                $table->timestamps();

                $table->index(['product_id', 'warehouse_id', 'fecha']);
                $table->index(['tipo_documento', 'movimiento']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
