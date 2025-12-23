<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('status', ['pending', 'verified'])->default('pending')->after('vehicle_id');
        });
    }

    public function down() {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
