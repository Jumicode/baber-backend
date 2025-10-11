<?php
//TODO: ERROR AL SUBIR ARCHIVO
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\Appointment;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/barbers/{barber_id}/payment-methods",
     * summary="Obtener los métodos de pago aceptados por un barbero específico.",
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="barber_id", in="path", required=true, description="ID del barbero", @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Métodos de pago obtenidos"),
     * @OA\Response(response=404, description="Barbero no encontrado")
     * )
     */
    public function getBarberPaymentMethods($barberId)
    {
        // 1. Encontrar el barbero y cargar los métodos de pago
        // Usamos with() para cargar la relación y acceder a los detalles del pivote.
        $barber = Barber::with(['paymentMethods'])->find($barberId);

        if (!$barber) {
            return response()->json(['message' => 'Barbero no encontrado.'], 404);
        }

        // 2. Formatear la respuesta para incluir los detalles de la cuenta
        $methods = $barber->paymentMethods->map(function ($method) {
            return [
                'id' => $method->id,
                'name' => $method->name,
                'description' => $method->description,
                // El campo 'details' proviene de la tabla pivote
                'details' => json_decode($method->pivot->details, true),
            ];
        });

        return response()->json([
            'message' => 'Métodos de pago del barbero obtenidos exitosamente.',
            'payment_methods' => $methods
        ], 200);
    }
    
/**
     * @OA\Post(
     * path="/api/appointments/{appointment_id}/payment",
     * summary="Subir comprobante de pago para una cita.",
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="appointment_id", in="path", required=true, description="ID de la cita", @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="multipart/form-data",
     * @OA\Schema(
     * @OA\Property(property="payment_method_id", type="integer", description="ID del método de pago utilizado"),
     * @OA\Property(property="payment_reference", type="string", description="Referencia o número de transacción"),
     * @OA\Property(property="payment_proof_file", type="string", format="binary", description="Archivo de imagen del comprobante")
     * )
     * )
     * ),
     * @OA\Response(response=200, description="Comprobante subido, esperando confirmación"),
     * @OA\Response(response=422, description="Error de validación o cita no válida")
     * )
     */
    public function uploadPaymentProof(Request $request, $appointmentId)
    {
        $appointment = Appointment::where('user_id', $request->user()->id)
                                ->where('id', $appointmentId)
                                ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada o no pertenece a este usuario.'], 404);
        }

        // 1. Validación estricta
        $request->validate([
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payment_reference' => ['required', 'string', 'max:255'],
            'payment_proof_file' => ['required', 'image', 'max:5120'], // Máximo 5MB
        ]);

        // 2. Validación de estado
        if ($appointment->payment_status === 'confirmed') {
            return response()->json(['message' => 'El pago para esta cita ya fue confirmado.'], 409);
        }
        
        // 3. Subir la imagen del comprobante
        $path = $request->file('payment_proof_file')->store('payment_proofs', 'public');

        // 4. Actualizar la cita
        $appointment->update([
            'payment_method_id' => $request->payment_method_id,
            'payment_reference' => $request->payment_reference,
            'payment_proof_path' => $path,
            'status' => 'pending', // El estado se mantiene en 'pending' hasta la confirmación manual
            'payment_status' => 'pending', // Podríamos usar 'uploaded' si quisiéramos un estado más fino
        ]);
        
        // 5. Devolver la respuesta
        return response()->json([
            'message' => 'Comprobante de pago subido correctamente. La cita queda en espera de confirmacion.',
            'appointment_id' => $appointment->id
        ], 200);
    }
}

