<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     * Los campos que pueden ser asignados masivamente.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number', // Campo añadido en la migración
        'photo_path',   // Campo añadido en la migración
        'role',         // Campo añadido en la migración
    ];

    /**
     * The attributes that should be hidden for serialization.
     * Campos que se ocultan al serializar el modelo.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     * Tipos de datos que deben ser casteados.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Relación definida previamente: Un usuario puede ser un barbero
    public function barber()
    {
        return $this->hasOne(Barber::class);
    }
    
    // Relación definida previamente: Un usuario (como cliente) puede tener muchas citas
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}