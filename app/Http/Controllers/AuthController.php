<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirect(Request $request, $provider)
    {
        // Redirige directamente usando el driver de Socialite
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback(Request $request, $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
            
            $user = User::updateOrCreate([
                'provider_id' => $socialUser->getId(),
            ], [
                'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                'email' => $socialUser->getEmail(),
                'provider_name' => $provider,
                'avatar' => $socialUser->getAvatar(),
            ]);

            // Assign a default role if newly created
            if ($user->wasRecentlyCreated) {
                // By default user has pending status and maybe 'propietario' role as default?
                // Let's defer role assignment until they update their profile or just assign 'propietario' temporarily.
                $user->assignRole('propietario');
            }

            // Create a token
            $token = $user->createToken('auth_token')->plainTextToken;

            // In a real SPA, we would redirect back to the SPA with the token in URL or set a cookie.
            // Assuming Vue frontend is on the same domain, we can redirect to a specific frontend route.
            return redirect('/login-callback?token=' . urlencode($token));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Socialite Login Error: ' . $e->getMessage());
            return redirect('/login?error=social_auth_failed');
        }
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['roles', 'properties']);
        return response()->json([
            'status' => 'success',
            'user' => $user
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'last_name' => 'nullable|string',
            'phone' => 'nullable|string',
            'whatsapp' => 'nullable|string',
            'avatar' => 'nullable|string', // URL for now
            'role' => 'nullable|string|in:propietario,arrendatario',
            'properties' => 'nullable|array',
            'properties.*.tower' => 'required|string',
            'properties.*.apartment' => 'required|string',
            'properties.*.type' => 'required|string|in:propietario,arrendatario',
            'observation' => 'nullable|string',
            'wants_desktop_notifications' => 'nullable|boolean',
            'wants_email_notifications' => 'nullable|boolean',
            'wants_whatsapp_notifications' => 'nullable|boolean'
        ]);

        $user->update($request->only(['last_name', 'phone', 'whatsapp', 'avatar', 'name', 'wants_desktop_notifications', 'wants_email_notifications', 'wants_whatsapp_notifications'])); 

        if (isset($validated['role'])) {
            if (!$user->hasRole(['master', 'admin'])) {
                $user->syncRoles([$validated['role']]);
            }
        }

        if (isset($validated['properties'])) {
             // 1. Get current properties for comparison
             $currentProps = $user->properties->map(fn($p) => [
                'tower' => (string)$p->tower, 
                'apartment' => (string)$p->apartment, 
                'type' => $p->type
             ])->toArray();

             $newProps = array_map(fn($p) => [
                'tower' => (string)$p['tower'], 
                'apartment' => (string)$p['apartment'], 
                'type' => $p['type']
             ], $validated['properties']);

             // Sort arrays to compare content regardless of order
             sort($currentProps);
             sort($newProps);

             $hasChanged = json_encode($currentProps) !== json_encode($newProps);

             if ($hasChanged) {
                 // Check for granular permission to edit properties
                 if (!$user->can('propiedades.gestionar') && !$user->hasRole(['master', 'admin'])) {
                     return response()->json(['error' => 'No tienes permiso para gestionar propiedades.'], 403);
                 }

                 // Only set status to 'pendiente' if the user is NOT a master or admin
                 if (!$user->hasRole(['master', 'admin'])) {
                    $user->update(['status' => 'pendiente']);
                    
                    // Create Notification for Admins
                    \App\Models\Notification::create([
                        'sender_id' => $user->id,
                        'type' => 'property_change',
                        'title' => 'Cambio de Propiedad / Solicitud Re-aprobación',
                        'message' => $validated['observation'] ?? 'Sin observación',
                        'data' => [
                            'properties' => $validated['properties']
                        ]
                    ]);

                    // Create official Solicitud (CommunityRequest)
                    \App\Models\CommunityRequest::create([
                        'user_id' => $user->id,
                        'type' => 'cambio_propiedad',
                        'title' => 'Actualización de unidades del perfil',
                        'description' => $validated['observation'] ?? 'Sin observación',
                        'data' => [
                            'properties' => $validated['properties']
                        ],
                        'status' => 'pendiente'
                    ]);
                 }

                // Sync properties only if they actually changed
                $user->properties()->delete();
                foreach ($validated['properties'] as $prop) {
                    $user->properties()->create($prop);
                }
             }
        }

        return response()->json(['status' => 'success', 'user' => $user->fresh()->load(['roles', 'properties', 'permissions'])]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();
        
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => asset('storage/' . $path)]);
        }

        return response()->json([
            'status' => 'success',
            'avatar' => $user->avatar
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['status' => 'success', 'message' => 'Logged out']);
    }
}
