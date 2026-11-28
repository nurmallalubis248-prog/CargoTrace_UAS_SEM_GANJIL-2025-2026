<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer shipment requests (status PENDING) do not carry a departure/arrival schedule:
 * the admin or operator who processes the request sets it before approving.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dateTime('expected_departure_at')->nullable()->change();
            $table->dateTime('expected_arrival_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dateTime('expected_departure_at')->nullable(false)->change();
            $table->dateTime('expected_arrival_at')->nullable(false)->change();
        });
    }
};
