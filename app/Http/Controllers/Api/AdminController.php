<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service; // Necesario para obtener precios
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/admin/appointments",
     * summary="Obtener lista consolidada de citas con filtros de fecha y estado. (Solo Admin/Barbero)",
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="start_date", in="query", description="Fecha de inicio (YYYY-MM-DD)"),
     * @OA\Parameter(name="end_date", in="query", description="Fecha de fin (YYYY-MM-DD)"),
     * @OA\Parameter(name="status", in="query", description="Filtrar por estado (confirmed, canceled, etc.)"),
     * @OA\Response(response=200, description="Lista de citas obtenida"),
     * @OA\Response(response=403, description="Acceso denegado")
     * )
     */
    public function getAppointments(Request $request)
    {
        $user = $request->user();

        // 1. Verificar Permisos (Autorización)
        // Damos acceso a Barbero para ver TODAS las citas (no solo las suyas) y a Admin.
        if ($user->role !== 'admin' && $user->role !== 'barber') {
            return response()->json(['message' => 'Acceso denegado. Se requiere ser barbero o administrador.'], 403);
        }

        // 2. Aplicar Filtros y Relaciones
        $query = Appointment::with(['client', 'barber.user', 'service'])
            ->orderBy('start_time', 'asc');

        try {
            // Validación de fechas
            $request->validate([
                'start_date' => ['nullable', 'date_format:Y-m-d'],
                'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
                'status' => ['nullable', 'string'], // No validamos el ENUM aquí para mayor flexibilidad
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error de formato en las fechas.', 'errors' => $e->errors()], 422);
        }
        
        // A. Filtro por Rango de Fechas
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            
            // Filtramos las citas cuyo inicio caiga dentro del rango (inclusivo)
            $query->whereBetween('start_time', [$startDate, $endDate]);

        } elseif ($request->filled('start_date')) {
            // Filtro para un solo día específico
            $date = Carbon::parse($request->input('start_date'))->startOfDay();
            $query->whereDate('start_time', $date);
        }

        // B. Filtro por Estado de Cita
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // 3. Ejecutar y Devolver la Respuesta
        $appointments = $query->get();

        return response()->json([
            'message' => 'Reporte de citas consolidado obtenido exitosamente.',
            'count' => $appointments->count(),
            'appointments' => $appointments,
        ], 200);
    }


    /**
     * @OA\Get(
     * path="/api/admin/dashboard-stats",
     * summary="Obtener estadísticas consolidadas del dashboard (Ingresos, conteo de estados). (Solo Admin/Barbero)",
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="start_date", in="query", description="Fecha de inicio (YYYY-MM-DD)"),
     * @OA\Parameter(name="end_date", in="query", description="Fecha de fin (YYYY-MM-DD)"),
     * @OA\Response(response=200, description="Estadísticas obtenidas"),
     * @OA\Response(response=403, description="Acceso denegado")
     * )
     */
    public function getDashboardStats(Request $request)
    {
        $user = $request->user();

        // 1. Verificar Permisos (Autorización)
        if ($user->role !== 'admin' && $user->role !== 'barber') {
            return response()->json(['message' => 'Acceso denegado. Se requiere ser barbero o administrador.'], 403);
        }

        try {
            // Validación de fechas
            $request->validate([
                'start_date' => ['nullable', 'date_format:Y-m-d'],
                'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error de formato en las fechas.', 'errors' => $e->errors()], 422);
        }
        
        // 2. Preparar el Query Base con Filtros de Fecha
        $query = Appointment::query();
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $query->whereBetween('start_time', [$startDate, $endDate]);
        } elseif ($request->filled('start_date')) {
            $date = Carbon::parse($request->input('start_date'))->startOfDay();
            $query->whereDate('start_time', $date);
        }

        // 3. Cálculos Principales (Se ejecuta una única vez)
        
        // Citas que fueron confirmadas (pagadas) o completadas
        $confirmedOrCompletedCitas = (clone $query)->whereIn('status', ['confirmed', 'completed'])->get();
        
        // Conteo de Citas por Estado (incluyendo el estado base de 'pending')
        $statusCounts = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map->count;
        
        // 4. Cálculo de Ingresos
        $totalRevenue = 0;
        if ($confirmedOrCompletedCitas->isNotEmpty()) {
            // Cargar los servicios para obtener los precios
            $confirmedOrCompletedCitas->load('service');
            
            // Sumar el precio de todos los servicios asociados a citas pagadas
            $totalRevenue = $confirmedOrCompletedCitas->sum(function ($appointment) {
                // Asume que el precio del servicio es el ingreso generado por la cita.
                return $appointment->service ? (float) $appointment->service->price : 0;
            });
        }

        // 5. Devolver la Respuesta
        return response()->json([
            'message' => 'Estadisticas del dashboard obtenidas exitosamente.',
            'period' => [
                'start' => $request->input('start_date') ?: 'Inicio de Histórico',
                'end' => $request->input('end_date') ?: 'Fin de Histórico',
            ],
            'metrics' => [
                'total_revenue' => number_format($totalRevenue, 2, '.', ''), // Ingresos generados
                'appointments_total' => (clone $query)->count(), // Total de citas en el periodo
                'appointments_confirmed' => $statusCounts->get('confirmed', 0) + $statusCounts->get('completed', 0), // Citas confirmadas + completadas
                'appointments_canceled' => $statusCounts->get('canceled', 0), // Citas canceladas
                'status_distribution' => $statusCounts, // Distribución detallada por estado
            ]
        ], 200);
    }
}