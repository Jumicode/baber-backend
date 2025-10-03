<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'user_id',
        'barber_id',
        'service_id',
        'payment_method_id',
        'start_time',
        'end_time',
        'status',
        'payment_status',
        'payment_proof_path',
        'payment_reference',
        'is_domicilio',
        'address_street',
        'address_city',
        'address_zip',
        'address_details',
    ];
    
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_domicilio' => 'boolean',
    ];

    // Relación: Acceso a los datos del cliente
    public function client()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación: Acceso al barbero
    public function barber()
    {
        return $this->belongsTo(Barber::class);
    }

    // Relación: Acceso a los detalles del servicio
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    // Relación: Acceso al método de pago elegido
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
