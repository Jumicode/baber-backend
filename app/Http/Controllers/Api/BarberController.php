<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\Service;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BarberController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/barbers/schedule",
     * summary="Obtener los horarios disponibles de los barberos para una fecha y servicio.",
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="date", in="query", required=true, description="Fecha para la que se busca disponibilidad (YYYY-MM-DD)", @OA\Schema(type="string")),
     * @OA\Parameter(name="service_id", in="query", required=true, description="ID del servicio para determinar la duración del slot", @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Disponibilidad de barberos obtenida"),
     * @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function schedule(Request $request)
    {
        try {
            // 1. Validar los parámetros de entrada
            $request->validate([
                'date' => ['required', 'date_format:Y-m-d'],
                'service_id' => ['required', 'exists:services,id'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Faltan parámetros de fecha o servicio.', 'errors' => $e->errors()], 422);
        }

        $requestedDate = Carbon::parse($request->input('date'));
        $dayOfWeek = $requestedDate->format('l'); // Nombre del día en inglés (Ej: 'Monday')
        $service = Service::find($request->service_id);
        $slotDuration = $service->duration;

        // 2. Obtener la lista de barberos activos y sus horarios para ese día
        $barbers = Barber::with(['user', 'schedules' => function ($query) use ($dayOfWeek) {
            $query->where('day_of_week', $dayOfWeek);
        }])->get();

        $allBarberAvailability = [];

        // 3. Iterar por cada barbero para calcular la disponibilidad
        foreach ($barbers as $barber) {
            // Un barbero puede tener 0 o 1 horario para un día específico
            $schedule = $barber->schedules->first(); 
            
            // 4. Si el barbero no trabaja ese día o no tiene horario, se salta.
            if (!$schedule || $schedule->is_day_off) {
                continue;
            }

            // 5. Obtener citas ocupadas para el barbero y la fecha
            $occupiedAppointments = Appointment::where('barber_id', $barber->id)
                ->whereDate('start_time', $requestedDate->toDateString())
                ->whereIn('status', ['pending', 'confirmed']) // Solo citas activas
                ->orderBy('start_time')
                ->get(['start_time', 'end_time']);

            $availableSlots = $this->calculateAvailability(
                $schedule->start_time,
                $schedule->end_time,
                $slotDuration,
                $occupiedAppointments
            );

            // 6. Almacenar el resultado
            if (!empty($availableSlots)) {
                $allBarberAvailability[] = [
                    'barber_id' => $barber->id,
                    'name' => $barber->user->name,
                    'photo_path' => $barber->user->photo_path,
                    'slots' => $availableSlots,
                ];
            }
        }

        return response()->json([
            'message' => 'Disponibilidad de barberos calculada exitosamente.',
            'date' => $requestedDate->toDateString(),
            'availability' => $allBarberAvailability,
        ], 200);
    }
    
    /**
     * Lógica privada para calcular slots disponibles.
     */
    private function calculateAvailability($startTime, $endTime, $duration, $occupiedAppointments)
    {
        $available = [];
        $currentTime = Carbon::parse($startTime);
        $endOfDay = Carbon::parse($endTime);

        // Bucle que recorre la jornada de trabajo
        while ($currentTime->lt($endOfDay)) {
            $slotStart = $currentTime->copy();
            $slotEnd = $slotStart->copy()->addMinutes($duration);

            // Si el slot excede el final de la jornada, se detiene
            if ($slotEnd->gt($endOfDay)) {
                break; 
            }

            $isOccupied = false;
            
            // Comprobar si este slot choca con alguna cita existente
            foreach ($occupiedAppointments as $appointment) {
                $apptStart = Carbon::parse($appointment->start_time);
                $apptEnd = Carbon::parse($appointment->end_time);

                // Lógica de colisión: el nuevo slot debe empezar DESPUÉS de que la cita termine 
                // Y debe terminar ANTES de que la cita empiece. 
                // Esto es una simplificación, la lógica más robusta debe estar en el agendamiento (POST /appointments)
                if ($slotStart->lt($apptEnd) && $slotEnd->gt($apptStart)) {
                    $isOccupied = true;
                    // Mover el puntero de tiempo al final de la cita ocupada para el siguiente ciclo
                    $currentTime = $apptEnd->copy(); 
                    break;
                }
            }

            // Si no está ocupado, se añade el slot y avanza el tiempo
            if (!$isOccupied) {
                // Solo agregar el slot si está en el futuro (para evitar que se agenden citas pasadas)
                if ($slotStart->gt(now())) {
                    $available[] = $slotStart->format('H:i'); 
                }
                $currentTime->addMinutes($duration); 
            }
        }

        return $available;
    }
}