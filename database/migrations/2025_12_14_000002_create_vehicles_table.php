<?php

// database/migrations/2025_12_14_000002_create_vehicles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('chassis', 17)->unique(); // Numéro de châssis / VIN
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->smallInteger('year')->nullable();
            $table->string('engine')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('vehicles');
    }
};
