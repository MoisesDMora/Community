<?php

namespace App\Http\Controllers;

use App\Models\CommunityRequest;
use App\Models\User;
use Illuminate\Http\Request;

class CommunityRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = CommunityRequest::with(['user', 'admin']);

        // IF NOT ADMIN, ONLY SHOW OWN REQUESTS
        if (!$user->hasRole(['master', 'admin'])) {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'status' => 'success',
            'requests' => $requests
        ]);
    }

    public function handle(Request $request, $id)
    {
        if (!$request->user()->hasRole(['master', 'admin'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:aprobada,rechazada',
            'admin_notes' => 'nullable|string'
        ]);

        $commRequest = CommunityRequest::findOrFail($id);
        $commRequest->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'],
            'admin_id' => $request->user()->id
        ]);

        // If approved, we activate the user corresponding to the request (since changing properties put them in 'pendiente')
        if ($validated['status'] === 'aprobada') {
            $user = $commRequest->user;
            if ($user && $user->status === 'pendiente') {
                $user->update(['status' => 'activo']);
            }
        }

        return response()->json([
            'status' => 'success', 
            'message' => 'Solicitud ' . $validated['status']
        ]);
    }

    public function pendingCount(Request $request)
    {
        if (!$request->user()->hasRole(['master', 'admin'])) {
            return response()->json(['count' => 0]);
        }

        $userReqCount = CommunityRequest::where('status', 'pendiente')->count();
        $resCount = \App\Models\CommonAreaReservation::where('status', 'pendiente')->count();
        
        return response()->json(['count' => $userReqCount + $resCount]);
    }
}
