<?php

// database/migrations/2025_12_14_000003_create_data_sources_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // garage, ct, obd
            $table->string('name');
            $table->boolean('verified')->default(false);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('data_sources');
    }
};
