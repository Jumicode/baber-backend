<?php

namespace App\Filament\Resources\Appointments\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

use Filament\Tables\Actions\Action as TableAction;
use Filament\Notifications\Notification;
use App\Models\Appointment;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('client.name')->label('Cliente')->searchable(),
                TextColumn::make('barber.user.name')->label('Barbero')->searchable(),
                TextColumn::make('service.name')->label('Servicio')->sortable(),
                TextColumn::make('start_time')->label('Inicio')->dateTime('M j, Y H:i')->sortable(),
                
                TextColumn::make('status')->label('Estado Cita')
                    ->badge() 
                    ->sortable(), 
                    
                TextColumn::make('payment_status')->label('Estado Pago')
                    ->badge()
                    ->sortable(),
                
                IconColumn::make('is_domicilio')->label('Domicilio')->boolean(),
            ])
            ->filters([
                // Si vas a usar filtros, debes añadir: use Filament\Tables\Filters\SelectFilter;
            ])
            ->actions([
                // Acción de Confirmar Pago
           TableAction::make('confirmPayment')
                    ->label('Aprobar Pago')
                    ->button()
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->hidden(fn (Appointment $record): bool => 
                        $record->payment_status !== 'pending' || is_null($record->payment_proof_path))
                    ->requiresConfirmation()
                    ->action(function (Appointment $record) {
                        $record->update([
                            'status' => 'confirmed',
                            'payment_status' => 'confirmed',
                        ]);
                        Notification::make()->title('Pago Aprobado')->success()->send();
                    }),
                
                // Acciones CRUD básicas
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_time', 'desc');
    }
}