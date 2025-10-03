<?php

// app/Models/Service.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'duration',
        'is_domicilio',
    ];

    // Relación: Un servicio puede estar en muchas citas
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
