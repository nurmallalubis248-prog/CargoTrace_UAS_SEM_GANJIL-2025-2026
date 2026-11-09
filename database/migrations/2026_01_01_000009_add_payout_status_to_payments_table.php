<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // PENDING_SETTLEMENT = money is with the payment gateway; SETTLED = the admin confirmed it reached the bank account.
            $table->string('payout_status', 20)->default('PENDING_SETTLEMENT')->after('status');
            $table->timestamp('settled_at')->nullable()->after('paid_at');
            $table->index('payout_status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payout_status']);
            $table->dropColumn(['payout_status', 'settled_at']);
        });
    }
};
