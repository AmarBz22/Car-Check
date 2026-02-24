<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('reports', function (Blueprint $table) {
            // Add partner_id if not exists
            if (!Schema::hasColumn('reports', 'partner_id')) {
                $table->foreignId('partner_id')->nullable()->constrained('users')->onDelete('set null');
            }

            // Add report type
            if (!Schema::hasColumn('reports', 'report_type')) {
                $table->enum('report_type', ['scanner', 'mechanic', 'auto_body_technician'])->default('scanner');
            }

            // Add detailed findings based on report type
            if (!Schema::hasColumn('reports', 'findings')) {
                $table->longText('findings')->nullable(); // JSON or text
            }

            // Scanner specific data
            if (!Schema::hasColumn('reports', 'kilometrage')) {
                $table->integer('kilometrage')->nullable(); // mileage from scanner
            }

            // Add status field
            if (!Schema::hasColumn('reports', 'status')) {
                $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            }

            // Add report date
            if (!Schema::hasColumn('reports', 'report_date')) {
                $table->timestamp('report_date')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeignIdFor('partner_id');
            $table->dropColumn([
                'partner_id',
                'report_type',
                'findings',
                'kilometrage',
                'status',
                'report_date',
            ]);
        });
    }
};
