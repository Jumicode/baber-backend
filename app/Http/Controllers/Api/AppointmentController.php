<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/appointments",
     * summary="Agendar una nueva cita",
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="barber_id", type="integer", example="1"),
     * @OA\Property(property="service_id", type="integer", example="1"),
     * @OA\Property(property="start_time", type="string", format="date-time", example="2025-10-15 10:00:00"),
     * @OA\Property(property="is_domicilio", type="boolean", example="true"),
     * @OA\Property(property="address_street", type="string", example="Av. Las Américas"),
     * @OA\Property(property="address_city", type="string", example="Puerto Ordaz")
     * )
     * ),
     * @OA\Response(response=201, description="Cita agendada exitosamente"),
     * @OA\Response(response=422, description="Error de validación o slot no disponible")
     * )
     */

    public function store(Request $request)
    {
        // 1. OBTENER DATOS DE SERVICIO Y DURACIÓN
        $service = Service::find($request->service_id);
        if (!$service) {
            return response()->json(['message' => 'Servicio no encontrado.'], 404);
        }
        $slotDuration = $service->duration;

        // Reglas de validación base
        $rules = [
            'barber_id' => ['required', 'exists:barbers,id'],
            'service_id' => ['required', 'exists:services,id'],
            'start_time' => ['required', 'date_format:Y-m-d H:i:s', 'after:now'],
            'is_domicilio' => ['required', 'boolean'],
        ];

        // 2. AÑADIR REGLAS CONDICIONALES PARA DOMICILIO
        if ($request->input('is_domicilio')) {
            $rules = array_merge($rules, [
                'address_street' => ['required', 'string', 'max:255'],
                'address_city' => ['required', 'string', Rule::in(['Puerto Ordaz', 'San Felix'])], // Validación de ciudades
                'address_zip' => ['nullable', 'string', 'max:50'],
                'address_details' => ['nullable', 'string'],
            ]);
            
            // Si el servicio no es a domicilio, se debe rechazar la solicitud
            if (!$service->is_domicilio) {
                return response()->json(['message' => 'El servicio seleccionado no esta disponible a domicilio.'], 422);
            }
        }
        
        try {
            $request->validate($rules);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error de validacion en la solicitud.', 'errors' => $e->errors()], 422);
        }
        
        $startTime = Carbon::parse($request->input('start_time'));
        $endTime = $startTime->copy()->addMinutes($slotDuration);
        $barberId = $request->barber_id;

        // 3. INICIO DE TRANSACCIÓN y VERIFICACIÓN DE DISPONIBILIDAD (CRÍTICO)
        // Usamos una transacción para asegurar que la verificación y la creación sean atómicas.
        try {
            $appointment = DB::transaction(function () use ($barberId, $startTime, $endTime, $request, $service) {
                
                // A. Verificar Horario de Jornada
                $barber = Barber::with(['schedules' => function ($query) use ($startTime) {
                    $query->where('day_of_week', $startTime->format('l'));
                }])->find($barberId);
                
                $schedule = $barber->schedules->first();
                
                // Verificar si trabaja ese día y si el slot está dentro de su jornada
                if (!$schedule || $schedule->is_day_off) {
                    throw new \Exception("El barbero no trabaja en la fecha solicitada.", 422);
                }
                
                $scheduleStart = Carbon::parse($startTime->format('Y-m-d') . ' ' . $schedule->start_time);
                $scheduleEnd = Carbon::parse($startTime->format('Y-m-d') . ' ' . $schedule->end_time);
                
                if ($startTime->lt($scheduleStart) || $endTime->gt($scheduleEnd)) {
                    throw new \Exception("La hora solicitada está fuera del horario de trabajo del barbero ({$schedule->start_time} - {$schedule->end_time}).", 422);
                }
                
                // B. Verificar Colisión con Citas Existentes
                $existingAppointment = Appointment::where('barber_id', $barberId)
                    ->whereIn('status', ['pending', 'confirmed'])
                    // Comprueba si alguna cita existente choca con el nuevo slot
                    ->where(function ($query) use ($startTime, $endTime) {
                        $query->where(function ($q) use ($startTime, $endTime) {
                            // Cita existente que comienza antes de que termine el nuevo slot
                            $q->where('start_time', '<', $endTime)
                              // Y termina después de que comienza el nuevo slot
                              ->where('end_time', '>', $startTime);
                        });
                    })
                    ->lockForUpdate() // Bloquea el registro para evitar concurrencia
                    ->first();

                if ($existingAppointment) {
                    throw new \Exception("La hora seleccionada ya esta reservada. Por favor, elige otro slot.", 422);
                }

                // C. CREAR LA CITA
                return Appointment::create([
                    'user_id' => $request->user()->id,
                    'barber_id' => $barberId,
                    'service_id' => $request->service_id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'is_domicilio' => $request->input('is_domicilio', false),
                    // Solo se llenan los campos de domicilio si aplica
                    'address_street' => $request->is_domicilio ? $request->address_street : null,
                    'address_city' => $request->is_domicilio ? $request->address_city : null,
                    'address_zip' => $request->is_domicilio ? $request->address_zip : null,
                    'address_details' => $request->is_domicilio ? $request->address_details : null,
                    // El estado inicial es 'pending' tanto para la cita como para el pago
                    'status' => 'pending', 
                    'payment_status' => 'pending',
                ]);
            });

            // 4. RESPUESTA DE ÉXITO
            return response()->json([
                'message' => 'Cita agendada exitosamente. Por favor, complete el pago.',
                'appointment_id' => $appointment->id,
            ], 201);

        } catch (\Exception $e) {
            // Manejar errores de validación o colisión
            $statusCode = ($e->getCode() === 422) ? 422 : 500;
            $errorMessage = $e->getMessage() ?: "Error al procesar la cita.";
            return response()->json(['message' => $errorMessage], $statusCode);
        }
    }

   /**
     * @OA\Get(
     * path="/api/appointments/mine",
     * summary="Obtener el historial de citas del cliente autenticado.",
     * security={{"sanctum":{}}},
     * @OA\Response(response=200, description="Lista de citas del cliente obtenida"),
     * @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @OA\Get(
     * path="/api/barber/appointments",
     * summary="Obtener la lista de citas pendientes del barbero autenticado.",
     * security={{"sanctum":{}}},
     * @OA\Response(response=200, description="Lista de citas del barbero obtenida"),
     * @OA\Response(response=403, description="Acceso denegado (No es barbero)")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Appointment::query();

        // 1. Determinar el rol y aplicar el filtro
        if ($request->route()->uri === 'api/appointments/mine') {
            // Caso: Cliente (Obtener mis citas)
            $query->where('user_id', $user->id);
            $query->with(['service', 'barber.user']); // Cliente necesita ver quién es el barbero
            
        } elseif ($request->route()->uri === 'api/barber/appointments') {
            // Caso: Barbero (Obtener mis citas)
            if ($user->role !== 'barber' || !$user->barber) {
                return response()->json(['message' => 'Acceso denegado. Solo barberos pueden acceder a esta ruta.'], 403);
            }
            
            $query->where('barber_id', $user->barber->id);
            $query->with(['service', 'client']); // Barbero necesita ver quién es el cliente
            
            // Los barberos generalmente quieren ver citas pendientes o confirmadas, ordenadas por tiempo
            $query->whereIn('status', ['pending', 'confirmed'])
                  ->orderBy('start_time', 'asc');

        } else {
            // Fallback (solo si el enrutamiento falla)
            return response()->json(['message' => 'Ruta de citas no reconocida.'], 400);
        }

        // 2. Ejecutar la consulta
        $appointments = $query->get();

        // 3. Formatear y devolver la respuesta
        return response()->json([
            'message' => 'Lista de citas obtenida exitosamente.',
            'appointments' => $appointments
        ], 200);
    }

}
