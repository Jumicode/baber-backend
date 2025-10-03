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
        Schema::create('appointments', function (Blueprint $table) {
              $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('Cliente');
    $table->foreignId('barber_id')->constrained('barbers')->onDelete('cascade');
    $table->foreignId('service_id')->constrained()->onDelete('cascade');
    $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');

    $table->timestamp('start_time');
    $table->timestamp('end_time');

    // Estado de la cita: pending, confirmed, completed, canceled
    $table->enum('status', ['pending', 'confirmed', 'completed', 'canceled'])->default('pending');
    // Estado del pago: pending, confirmed
    $table->enum('payment_status', ['pending', 'confirmed'])->default('pending');
    
    // Campos para la gestión de pagos
    $table->string('payment_proof_path')->nullable();
    $table->string('payment_reference')->nullable();

    // Campos para el servicio a domicilio
    $table->boolean('is_domicilio')->default(false);
    $table->string('address_street')->nullable();
    $table->string('address_city')->nullable(); // Validación en la lógica: 'Puerto Ordaz' o 'San Felix'
    $table->string('address_zip')->nullable();
    $table->text('address_details')->nullable(); // Notas o referencias

    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
