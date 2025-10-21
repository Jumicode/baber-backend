<?php

namespace App\Filament\Widgets; // O el namespace que Filament te haya dado

use App\Models\Appointment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 0; 

    protected function getStats(): array
    {
        // Define el periodo de análisis (últimos 30 días)
        $startDate = Carbon::now()->subDays(30);

        // 1. Citas Pagadas (confirmed o completed)
        $confirmedOrCompletedCitas = Appointment::query()
            ->whereBetween('start_time', [$startDate, Carbon::now()])
            ->whereIn('status', ['confirmed', 'completed'])
            ->with('service')
            ->get();

        // 2. Cálculo de Ingresos
        $totalRevenue = $confirmedOrCompletedCitas->sum(function ($appointment) {
            return $appointment->service ? (float) $appointment->service->price : 0;
        });

        // 3. Conteo de citas por estado
        $statusCounts = Appointment::query()
            ->whereBetween('start_time', [$startDate, Carbon::now()])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map->count;

        // 4. Retornar las métricas de Filament
        return [
            Stat::make('Ingresos Totales (30 días)', '$' . number_format($totalRevenue, 2))
                ->description('Ingreso generado por citas pagadas')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Citas Confirmadas', $statusCounts->get('confirmed', 0) + $statusCounts->get('completed', 0))
                ->description('Citas aseguradas en los últimos 30 días')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('Citas Canceladas', $statusCounts->get('canceled', 0))
                ->description('Total de citas perdidas por cancelación')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}