<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->hasRole(['master', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = User::with(['roles', 'properties', 'permissions']);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('role') && $request->role) {
            $query->role($request->role);
        }

        if ($request->has('tower') && $request->tower) {
            $query->whereHas('properties', function($q) use ($request) {
                $q->where('tower', 'like', '%' . $request->tower . '%');
            });
        }

        if ($request->has('apartment') && $request->apartment) {
            $query->whereHas('properties', function($q) use ($request) {
                $q->where('apartment', 'like', '%' . $request->apartment . '%');
            });
        }

        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function updateUserProperties(Request $request, $id)
    {
        if (!$request->user()->hasRole(['master', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);
        $request->validate([
            'properties' => 'required|array',
            'properties.*.tower' => 'required|string',
            'properties.*.apartment' => 'required|string',
            'properties.*.type' => 'required|string'
        ]);

        $user->properties()->delete();
        foreach ($request->properties as $prop) {
            $user->properties()->create($prop);
        }

        return response()->json(['status' => 'success', 'user' => $user->fresh()->load('properties')]);
    }

    public function approve(Request $request, $id)
    {
        if (!$request->user()->hasRole(['master', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);
        $user->update(['status' => 'activo']);

        return response()->json(['message' => 'User approved', 'user' => $user->load(['roles', 'permissions'])]);
    }

    public function updateRole(Request $request, $id)
    {
        if (!$request->user()->hasRole(['master', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);
        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name'
        ]);

        // Guard against demoting self
        if ($id == $request->user()->id && $validated['role'] !== 'master' && $request->user()->hasRole('master')) {
            return response()->json(['error' => 'No puedes degradar tu propio rol Master'], 403);
        }

        $user->syncRoles([$validated['role']]);

        return response()->json(['message' => 'Role updated', 'user' => $user->load(['roles', 'properties', 'permissions'])]);
    }

    public function disapprove(Request $request, $id)
    {
        if (!$request->user()->hasRole(['master', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);
        $user->update(['status' => 'inactivo']);

        return response()->json(['message' => 'User disapproved', 'user' => $user->load(['roles', 'permissions'])]);
    }
}
