<?php

namespace App\Http\Controllers;

use App\Models\CommonAreaReservation;
use Illuminate\Http\Request;

class CommonAreaReservationController extends Controller
{
    public function index(Request $request, $area_id)
    {
        $reservations = CommonAreaReservation::with('user:id,name,last_name')
            ->where('common_area_id', $area_id)
            ->whereIn('status', ['pendiente', 'aprobada'])
            ->get();
        return response()->json(['data' => $reservations]);
    }

    public function store(Request $request, $area_id)
    {
        $validated = $request->validate([
            'start_time'   => 'required|date',
            'end_time'     => 'required|date|after:start_time',
            'people_count' => 'required|integer|min:1',
            'notes'        => 'nullable|string'
        ]);

        $area = \App\Models\CommonArea::findOrFail($area_id);

        if ($validated['people_count'] > $area->max_people) {
            return response()->json(['error' => 'La cantidad de personas excede el aforo máximo de la zona (' . $area->max_people . ').'], 422);
        }

        // Sumar personas concurrentes en el mismo horario
        $overlappingReservations = CommonAreaReservation::where('common_area_id', $area_id)
            ->whereIn('status', ['pendiente', 'aprobada'])
            ->where(function ($q) use ($validated) {
                $q->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                  ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                  ->orWhere(function ($q2) use ($validated) {
                      $q2->where('start_time', '<=', $validated['start_time'])
                         ->where('end_time', '>=', $validated['end_time']);
                  });
            })->get();

        $currentPeopleAcc = $overlappingReservations->sum('people_count');

        if (($currentPeopleAcc + $validated['people_count']) > $area->max_people) {
            return response()->json(['error' => 'El aforo disponible en ese horario es de solo ' . ($area->max_people - $currentPeopleAcc) . ' personas. Ya hay reservaciones hechas en ese bloque.'], 422);
        }

        // Calcular tarifa
        $calculatedFee = 0;
        if ($area->fee_type === 'per_person') {
            $calculatedFee = $area->fee_amount * $validated['people_count'];
        } elseif ($area->fee_type === 'per_time') {
            $calculatedFee = $area->fee_amount;
        }

        $res = CommonAreaReservation::create([
            'common_area_id' => $area_id,
            'user_id'        => $request->user()->id,
            'start_time'     => $validated['start_time'],
            'end_time'       => $validated['end_time'],
            'people_count'   => $validated['people_count'],
            'status'         => 'pendiente',
            'notes'          => $validated['notes'] ?? null,
            'calculated_fee' => $calculatedFee,
        ]);

        return response()->json(['status' => 'success', 'data' => $res]);
    }

    public function myReservations(Request $request) {
        $reservations = CommonAreaReservation::with('commonArea')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['data' => $reservations]);
    }

    public function adminIndex(Request $request) {
        if (!$request->user()->hasRole(['master', 'admin'])) return response()->json(['error'=>'Unauthorized'],403);
        $reservations = CommonAreaReservation::with(['commonArea', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['data' => $reservations]);
    }

    public function updateStatus(Request $request, $id) {
        if (!$request->user()->hasRole(['master', 'admin'])) return response()->json(['error'=>'Unauthorized'],403);
        
        $res = CommonAreaReservation::with('commonArea')->findOrFail($id);
        
        $validated = $request->validate([
            'status'           => 'required|in:aprobada,rechazada',
            'rejection_reason' => 'nullable|string|max:1000'
        ]);

        $updateData = ['status' => $validated['status']];
        if ($validated['status'] === 'rechazada' && !empty($validated['rejection_reason'])) {
            $updateData['rejection_reason'] = $validated['rejection_reason'];
        }

        $res->update($updateData);

        // Armar mensaje de notificación
        $msg = 'Tu reserva para ' . $res->commonArea->name . ' ha sido ' . $validated['status'] . '.';
        if ($validated['status'] === 'rechazada' && !empty($validated['rejection_reason'])) {
            $msg .= ' Motivo: ' . $validated['rejection_reason'];
        } elseif ($validated['status'] === 'aprobada' && $res->calculated_fee > 0) {
            $feeLine = number_format($res->calculated_fee, 2);
            $msg .= ' La tarifa calculada es de $' . $feeLine . '.';
        }

        // Enviar notificación al usuario
        \App\Models\Notification::create([
            'sender_id'    => $request->user()->id,
            'recipient_id' => $res->user_id,
            'title'        => 'Reserva de Zona Común ' . ucfirst($validated['status']),
            'message'      => $msg,
            'type'         => 'reservation_update',
            'is_read'      => false
        ]);

        return response()->json(['status' => 'success', 'data' => $res]);
    }
}
