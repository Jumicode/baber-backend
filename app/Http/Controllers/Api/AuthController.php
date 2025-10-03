<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/register",
     * summary="Registro de un nuevo usuario (Cliente)",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="name", type="string", example="Juan Pérez"),
     * @OA\Property(property="email", type="string", format="email", example="juan.perez@example.com"),
     * @OA\Property(property="password", type="string", example="password123"),
     * @OA\Property(property="phone_number", type="string", example="584121234567")
     * )
     * ),
     * @OA\Response(response=201, description="Usuario registrado exitosamente"),
     * @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function register(Request $request)
    {
        try {
            // 1. Validar los datos de entrada
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8'],
                'phone_number' => ['required', 'string', 'max:50'],
                // El campo role no se pide al cliente, se asigna por defecto 'client'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error de validacion', 'errors' => $e->errors()], 422);
        }

        // 2. Crear el usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'role' => 'client', // Se asigna automáticamente el rol 'client'
        ]);

        // 3. Crear el token con Laravel Sanctum
        $token = $user->createToken('authToken')->plainTextToken;

        // 4. Devolver la respuesta de éxito
        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ]
        ], 201);
    }

    /**
     * @OA\Post(
     * path="/api/login",
     * summary="Inicio de sesión de usuario",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="email", type="string", format="email", example="juan.perez@example.com"),
     * @OA\Property(property="password", type="string", example="password123")
     * )
     * ),
     * @OA\Response(response=200, description="Inicio de sesión exitoso"),
     * @OA\Response(response=401, description="Credenciales incorrectas")
     * )
     */
    public function login(Request $request)
    {
        // 1. Validar los datos de entrada
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2. Intentar autenticar al usuario
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        // 3. Obtener el usuario autenticado
        $user = $request->user();

        // 4. Revocar tokens antiguos y crear uno nuevo (por seguridad)
        $user->tokens()->delete(); // Opcional, pero recomendado
        $token = $user->createToken('authToken')->plainTextToken;

        // 5. Devolver la respuesta de éxito
        return response()->json([
            'message' => 'Inicio de sesion exitoso',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ]
        ], 200);
    }

    /**
     * @OA\Get(
     * path="/api/profile",
     * summary="Obtener los datos del perfil del usuario autenticado",
     * security={{"sanctum":{}}},
     * @OA\Response(response=200, description="Datos del perfil obtenidos"),
     * @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function profile(Request $request)
    {
        // Obtiene el usuario autenticado (el token lo provee)
        $user = $request->user();

        // Si el usuario es un barbero, podemos cargar información adicional.
        if ($user->role === 'barber') {
            // Carga la relación 'barber' para obtener la bio y otros datos
            $user->load('barber');
            
            // Accede a los métodos de pago a través de la relación de barbero
            $barber = $user->barber;
            if ($barber) {
                // Carga los métodos de pago y el detalle del pivote
                $barber->load(['paymentMethods' => function($query) {
                    $query->select('payment_methods.id', 'payment_methods.name');
                }]);
            }
        }
        
        // El método 'makeHidden' oculta el campo de la contraseña antes de responder
        return response()->json([
            'user' => $user->makeHidden('password'),
        ]);
    }

    /**
     * @OA\Post(
     * path="/api/logout",
     * summary="Cerrar sesión (revocar token)",
     * security={{"sanctum":{}}},
     * @OA\Response(response=200, description="Sesión cerrada exitosamente"),
     * @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesion cerrada exitosamente.'], 200);
    }
}