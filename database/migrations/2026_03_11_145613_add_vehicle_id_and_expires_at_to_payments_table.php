<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('payments', function (Blueprint $table) {
        // Only add expires_at if it doesn't already exist
        if (!Schema::hasColumn('payments', 'expires_at')) {
            $table->timestamp('expires_at')->nullable()->after('status');
        }

        // Only add vehicle_id if it doesn't already exist
        if (!Schema::hasColumn('payments', 'vehicle_id')) {
            $table->foreignId('vehicle_id')->nullable()->constrained()->cascadeOnDelete();
        }
    });
}

public function down(): void
{
    Schema::table('payments', function (Blueprint $table) {
        $table->dropForeign(['vehicle_id']);
        $table->dropColumn(['vehicle_id', 'expires_at']);
    });
}
};
