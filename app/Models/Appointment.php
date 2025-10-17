<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @OA\Schema(
 * schema="Appointment",
 * title="Cita",
 * description="Detalles de una cita agendada en la barbería.",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="user_id", type="integer", description="ID del cliente"),
 * @OA\Property(property="barber_id", type="integer", description="ID del barbero"),
 * @OA\Property(property="service_id", type="integer", description="ID del servicio"),
 * @OA\Property(property="start_time", type="string", format="date-time", example="2025-10-15T10:00:00Z"),
 * @OA\Property(property="status", type="string", enum={"pending", "confirmed", "canceled", "completed"}),
 * @OA\Property(property="payment_status", type="string", enum={"pending", "confirmed", "refund_pending"}),
 * @OA\Property(property="is_domicilio", type="boolean", example=false),
 * @OA\Property(property="address_street", type="string", example="Calle Falsa 123"),
 * @OA\Property(property="address_city", type="string", example="Ciudad Ejemplo"),
 * @OA\Property(property="address_zip", type="string", example="28013"),
 * @OA\Property(property="address_details", type="string", example="Piso 2, Puerta B"),
 * )
 */
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
