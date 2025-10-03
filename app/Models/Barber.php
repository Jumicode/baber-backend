<?php

// app/Models/Barber.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barber extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
    ];

    // Relación: Un barbero pertenece a un usuario (acceso a name, email, phone)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación: Un barbero tiene muchas citas
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    // Relación: Un barbero tiene muchos horarios (uno para cada día)
    public function schedules()
    {
        return $this->hasMany(BarberSchedule::class);
    }

    // Relación: Un barbero tiene muchos métodos de pago (relación muchos a muchos)
    public function paymentMethods()
    {
        return $this->belongsToMany(PaymentMethod::class, 'barber_payment_methods')
                    ->withPivot('details'); // Accede al campo JSON 'details' de la tabla pivote
    }
}
