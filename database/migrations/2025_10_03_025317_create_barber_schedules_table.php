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
        Schema::create('barber_schedules', function (Blueprint $table) {
             $table->id();
    $table->foreignId('barber_id')->constrained()->onDelete('cascade');
    $table->string('day_of_week'); // Ej: 'Monday', 'Tuesday'
    $table->time('start_time');
    $table->time('end_time');
    $table->boolean('is_day_off')->default(false);
    // Asegura que no haya dos horarios para el mismo barbero en el mismo día.
    $table->unique(['barber_id', 'day_of_week']); 
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barber_schedules');
    }
};
