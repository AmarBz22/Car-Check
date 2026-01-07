<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('vehicle_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vehicle_id')
                ->constrained('vehicles')
                ->onDelete('cascade');

            $table->string('image_path');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_images');
    }
};
