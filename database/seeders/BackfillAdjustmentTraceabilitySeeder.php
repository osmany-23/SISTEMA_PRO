<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillAdjustmentTraceabilitySeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('adjustments')) {
            return;
        }

        $userId = User::query()->orderBy('id')->value('id');

        if ($userId && Schema::hasColumn('adjustments', 'user_id')) {
            DB::table('adjustments')
                ->whereNull('user_id')
                ->update([
                    'user_id' => $userId,
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasColumn('adjustments', 'descripcion')) {
            DB::table('adjustments')
                ->where(function ($query) {
                    $query->whereNull('descripcion')
                        ->orWhere('descripcion', '');
                })
                ->update([
                    'descripcion' => 'Ajuste de inventario sin motivo registrado',
                    'updated_at' => now(),
                ]);
        }
    }
}
