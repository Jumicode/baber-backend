<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Barber;
use App\Models\Service;
use App\Models\PaymentMethod;
use App\Models\BarberSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ----------------------
        // 1. CREAR UN USUARIO BARBERO
        // ----------------------
        $barberUser = User::create([
            'name' => 'Barbero David',
            'email' => 'david.barber@randell.com',
            'password' => Hash::make('password'),
            'phone_number' => '584121112233',
            'role' => 'barber',
        ]);

        // Crear el modelo Barber asociado
        $barber = Barber::create([
            'user_id' => $barberUser->id,
            'bio' => 'Especialista en cortes modernos y degradados. Más de 5 años de experiencia.',
        ]);

        // ----------------------
        // 2. CREAR UN SERVICIO DE PRUEBA
        // ----------------------
        $service = Service::create([
            'name' => 'Corte Express',
            'description' => 'Corte rápido con máquina.',
            'price' => 8.50,
            'duration' => 30, // 30 minutos
            'is_domicilio' => false,
        ]);
        
        // ----------------------
        // 3. CREAR MÉTODOS DE PAGO (Opcional, pero necesario para la prueba completa)
        // ----------------------
        $pagoMovil = PaymentMethod::create(['name' => 'Pago Móvil', 'description' => 'Transferencia al instante.']);
        $transferencia = PaymentMethod::create(['name' => 'Transferencia', 'description' => 'Transferencia Bancaria.']);

        // Asignar Pago Móvil al Barbero
        $barber->paymentMethods()->attach($pagoMovil->id, [
            'details' => json_encode([
                'banco' => 'Banesco', 
                'cedula' => 'V-12345678', 
                'telefono' => '0412-1112233'
            ])
        ]);

        // ----------------------
        // 4. CREAR HORARIO DE TRABAJO (CRÍTICO PARA LA DISPONIBILIDAD)
        // ----------------------
        
        // Obtener el nombre del día de la semana de la fecha de prueba (2025-10-15)
        // El 15 de octubre de 2025 es un Wednesday (Miércoles)
        $testDate = Carbon::parse('2025-10-15');
        $dayOfWeek = $testDate->format('l'); // 'Wednesday'

        BarberSchedule::create([
            'barber_id' => $barber->id,
            'day_of_week' => $dayOfWeek, 
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_day_off' => false,
        ]);

        $this->command->info('Barbero David, Servicio (ID: ' . $service->id . ') y Horario creados para el ' . $dayOfWeek);
    }
}