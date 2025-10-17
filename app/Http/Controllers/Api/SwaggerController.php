<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="API Randell Barber",
 * description="Documentación de la API para la aplicación móvil Randell Barber. Incluye gestión de citas, pagos y administración.",
 * @OA\Contact(
 * email="soporte@randellbarber.com"
 * ),
 * @OA\License(
 * name="Licencia Privada"
 * )
 * )
 *
 * @OA\SecurityScheme(
 * securityScheme="sanctum",
 * type="http",
 * scheme="bearer",
 * bearerFormat="JWT"
 * )
 */
class SwaggerController extends \App\Http\Controllers\Controller
{
   
}
