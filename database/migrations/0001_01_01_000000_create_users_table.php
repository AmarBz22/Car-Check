<?php

// database/migrations/2025_12_14_000001_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('client'); // admin, source, client
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('users');
    }
};

