<?php

namespace App\Filament\Resources\Appointments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use App\Models\User;
use App\Models\Barber;
use App\Models\Service;
use App\Models\PaymentMethod;

class AppointmentForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                // Relaciones Obligatorias
                Select::make('user_id')->label('Cliente')
                    ->options(User::where('role', 'client')->pluck('name', 'id'))
                    ->searchable()->required(),
                
                Select::make('barber_id')->label('Barbero')
                    // Asume que el modelo Barber tiene una relación 'user' para obtener el nombre
                    ->relationship('barber', 'user.name') 
                    ->searchable()->required(),
                
                Select::make('service_id')->label('Servicio')
                    ->relationship('service', 'name')
                    ->searchable()->required(),

                // Horario
                DateTimePicker::make('start_time')->label('Inicio Cita')
                    ->required(),
                DateTimePicker::make('end_time')->label('Fin Cita')
                    ->required(),

                // Estado de la Cita y Pago
                Select::make('status')->label('Estado Cita')
                    ->options([
                        'pending' => 'Pendiente', 
                        'confirmed' => 'Confirmada', 
                        'canceled' => 'Cancelada', 
                        'completed' => 'Completada'
                    ])
                    ->default('pending')->required(),
                
                Select::make('payment_status')->label('Estado Pago')
                    ->options([
                        'pending' => 'Pendiente', 
                        'confirmed' => 'Confirmado', 
                        'refund_pending' => 'Reembolso Pendiente'
                    ])
                    ->default('pending')->required(),
                
                // Información de Pago
                Select::make('payment_method_id')->label('Método de Pago Usado')
                    ->relationship('paymentMethod', 'name')
                    ->nullable(),
                TextInput::make('payment_reference')->label('Referencia de Pago')->nullable(),
                // Nota: El comprobante (payment_proof_path) es un path y puede no ser editable aquí.

                // Servicio a Domicilio
                Toggle::make('is_domicilio')->label('A Domicilio'),
                TextInput::make('address_street')->label('Calle / Dirección')->nullable()->columnSpan(2),
                TextInput::make('address_city')->label('Ciudad')->nullable(),
                TextInput::make('address_zip')->label('Código Postal')->nullable(),
                TextInput::make('address_details')->label('Detalles Adicionales')->columnSpan('full')->nullable(),
            ])
            ->columns(3);
    }
}