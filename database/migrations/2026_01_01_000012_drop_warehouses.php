<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Warehouses are not part of the brief, so the `warehouses` table and shipments.warehouse_id are removed.
 * On a fresh database this is a no-op; on a database that still has them it drops them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('shipments', 'warehouse_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('warehouse_id');
            });
        }
        Schema::dropIfExists('warehouses');
    }

    public function down(): void
    {
        // Intentionally empty: warehouses are not part of the project any more.
    }
};
