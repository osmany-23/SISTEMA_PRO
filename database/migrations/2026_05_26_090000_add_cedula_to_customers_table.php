<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers') || Schema::hasColumn('customers', 'cedula')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->string('cedula')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('customers', 'cedula')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('cedula');
        });
    }
};
