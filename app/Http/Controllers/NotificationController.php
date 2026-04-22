<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Notification::with('sender')
            ->orderBy('created_at', 'desc');

        // IF ADMIN, show admin notifications (recipient_id IS NULL)
        if ($user->hasRole(['master', 'admin'])) {
            $query->whereNull('recipient_id');
        } else {
            // IF RESIDENT, show only their notifications
            $query->where('recipient_id', $user->id);
        }

        return response()->json([
            'status' => 'success',
            'notifications' => $query->paginate(20)
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);
        
        // Ensure user can only mark their own or if admin marking admin notification
        if (!$request->user()->hasRole(['master', 'admin']) && $notification->recipient_id !== $request->user()->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $notification->update(['is_read' => true]);
        return response()->json(['status' => 'success']);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $query = Notification::where('is_read', false);

        if ($user->hasRole(['master', 'admin'])) {
            $query->whereNull('recipient_id');
        } else {
            $query->where('recipient_id', $user->id);
        }

        $query->update(['is_read' => true]);
        return response()->json(['status' => 'success']);
    }

    public function destroy(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);
        
        if (!$request->user()->hasRole(['master', 'admin']) && $notification->recipient_id !== $request->user()->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $notification->delete();
        return response()->json(['status' => 'success']);
    }

    public function unreadCount(Request $request)
    {
        $user = $request->user();
        $query = Notification::where('is_read', false);

        if ($user->hasRole(['master', 'admin'])) {
            $query->whereNull('recipient_id');
        } else {
            $query->where('recipient_id', $user->id);
        }

        return response()->json(['count' => $query->count()]);
    }

    public function sendToUser(Request $request)
    {
        if (!$request->user()->hasRole(['master', 'admin'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string'
        ]);

        $notif = Notification::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $validated['user_id'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'type' => $validated['type'] ?? 'admin_message',
            'is_read' => false
        ]);

        return response()->json(['status' => 'success', 'notification' => $notif]);
    }
}
