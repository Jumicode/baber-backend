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
        Schema::create('barber_payment_methods', function (Blueprint $table) {
    $table->id();
    $table->foreignId('barber_id')->constrained()->onDelete('cascade');
    $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
    $table->jsonb('details')->nullable()->comment('JSON para datos de cuenta: Cédula, banco, etc.');
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barber_payment_methods');
    }
};
