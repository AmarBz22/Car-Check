<?php

// database/migrations/2025_12_14_000004_create_vehicle_records_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('vehicle_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('data_source_id')->constrained('data_sources')->onDelete('cascade');
            $table->string('record_type'); // inspection, maintenance, obd
            $table->integer('mileage')->nullable();
            $table->json('details')->nullable(); // flexible info
            $table->date('recorded_at');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('vehicle_records');
    }
};
