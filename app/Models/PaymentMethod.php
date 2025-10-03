<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    // Relación: Muchos métodos de pago son aceptados por muchos barberos
    public function barbers()
    {
        return $this->belongsToMany(Barber::class, 'barber_payment_methods')
                    ->withPivot('details');
    }
}
