<?php

// database/migrations/2025_12_14_000001_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
    Schema::create('users', function (Blueprint $table) {
    $table->id();

    // Identity
    $table->string('name', 150);
    $table->string('email', 150)->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');

    // Role & Status
    $table->enum('role', ['admin', 'partner', 'client'])->default('client');
    $table->enum('status', ['active', 'suspended'])->default('active');
    

    $table->timestamps();
});
    }

    public function down() {
        Schema::dropIfExists('users');
    }
};

