<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
{
    Schema::table('vehicles', function (Blueprint $table) {
        $table->dropColumn('status');
    });
}

public function down(): void
{
    Schema::table('vehicles', function (Blueprint $table) {
        $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending')->after('color');
    });
}
};
