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
        Schema::create('spots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->index();
            $table->string('prefecture', 10)->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('visibility', 20)->default('private')->index();
            $table->string('caution_type', 20)->nullable();
            $table->string('parking_type', 20)->nullable();
            $table->string('parking_note')->nullable();
            $table->string('toilet_available', 20)->nullable();
            $table->string('toilet_note')->nullable();
            $table->integer('convenience_distance_m')->nullable();
            $table->text('facility_note')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spots');
    }
};
