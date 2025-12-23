<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('plate_number')->unique();     // رقم لوحة السيارة
            $table->string('vin')->nullable()->unique();  // رقم الهيكل (VIN)

            // Basic info
            $table->string('brand');        // العلامة التجارية (Peugeot, Toyota...)
            $table->string('model');        // الموديل (208, Corolla...)
            $table->year('year');           // سنة الصنع
            $table->string('color')->nullable(); // اللون

            // Status & source
            $table->enum('status', ['pending', 'verified', 'rejected'])
                  ->default('pending');     // حالة التحقق
            $table->foreignId('verified_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();          // الشخص الذي تحقق من السيارة

            // Metadata
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicles');
    }
};
