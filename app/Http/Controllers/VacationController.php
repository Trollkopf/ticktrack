<?php

namespace App\Http\Controllers;

use App\Models\Vacation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class VacationController extends Controller
{
    // Vista principal de vacaciones
    public function index()
    {
        $user = Auth::user();

        // Obtener todas las vacaciones del usuario
        $vacations = Vacation::where('user_id', $user->id)->get();

        // Calcular días de vacaciones confirmados y solicitados
        $confirmedDays = $vacations->where('validated', true)->sum('total_days');
        $requestedDays = $vacations->where('validated', false)->sum('total_days');

        // Días disfrutados (confirmados) y días restantes considerando confirmados y solicitados
        $usedDays = $confirmedDays;
        $remainingDays = 30 - ($usedDays + $requestedDays); // Días restantes considerando ambos

        return view('user.vacations', [
            'vacations' => $vacations,
            'remainingDays' => $remainingDays,
            'usedDays' => $usedDays,
            'requestedDays' => $requestedDays,
            'confirmedDays' => $confirmedDays,
            'vacationDates' => $vacations->where('validated', true)->pluck('start_date', 'end_date')
        ]);
    }

    // Solicitar nuevas vacaciones
    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $user = Auth::user();

        // Calcular el total de días solicitados
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Crear la solicitud de vacaciones
        Vacation::create([
            'user_id' => $user->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total_days' => $totalDays,
            'validated' => false,
            'refused' => false,
        ]);

        return redirect()->route('vacations.index')->with('success', 'Solicitud de vacaciones enviada.');
    }
}