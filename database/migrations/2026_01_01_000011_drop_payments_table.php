<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The brief does not include payments, so the old `payments` table (and its payout columns) is removed.
 * On a fresh database this is a no-op; on a database that still has the table it drops it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('payments');
    }

    public function down(): void
    {
        // Intentionally empty: payments are not part of the project any more.
    }
};
