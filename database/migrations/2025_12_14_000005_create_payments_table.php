<?php

// database/migrations/2025_12_14_000005_create_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('chassis', 17); // link to vehicle by chassis
            $table->integer('amount');
            $table->string('currency', 10)->default('DZD');
            $table->string('chargily_payment_id')->nullable();
            $table->string('status')->default('pending'); // pending, paid, failed
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('payments');
    }
};
