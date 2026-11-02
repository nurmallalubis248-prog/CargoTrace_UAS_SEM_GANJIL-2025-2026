<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number', 30)->unique();
            $table->string('reference_number', 50)->nullable()->index();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('carrier_id')->constrained('carriers')->restrictOnDelete();
            $table->foreignId('origin_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('destination_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('cargo_type', 30);
            $table->decimal('weight_kg', 12, 2);
            $table->decimal('volume_cbm', 12, 2)->nullable();
            $table->dateTime('expected_departure_at');
            $table->dateTime('expected_arrival_at')->index();
            $table->dateTime('estimated_arrival_at')->nullable(); // ETA, updated by tracking events
            $table->dateTime('actual_arrival_at')->nullable();
            $table->string('current_status', 20)->default('CREATED')->index();

            // Denormalised "current location" = latest valid tracking event with coordinates.
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->string('current_location_label')->nullable();
            $table->dateTime('last_event_at')->nullable();

            // Result of EtaService (stored so lists/filters/dashboard can query it in SQL).
            $table->boolean('is_delayed')->default(false)->index();
            $table->decimal('delay_hours', 8, 2)->default(0);
            $table->string('delay_severity', 10)->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('weight_kg', 12, 2)->nullable();
            $table->decimal('volume_cbm', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 20); // STATUS_UPDATE | CHECKPOINT | ETA_UPDATE | EXCEPTION
            $table->string('status', 20)->nullable(); // only for STATUS_UPDATE
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('location_label')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->dateTime('occurred_at');
            $table->text('description')->nullable();
            $table->boolean('is_exception')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shipment_id', 'occurred_at']);
        });

        Schema::create('shipment_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('severity', 10);
            $table->string('status', 15)->default('OPEN');
            $table->text('description');
            $table->dateTime('detected_at');
            $table->dateTime('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shipment_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_exceptions');
        Schema::dropIfExists('tracking_events');
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
    }
};
