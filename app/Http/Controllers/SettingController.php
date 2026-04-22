<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'settings' => Setting::all()->pluck('value', 'key'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->all();

        foreach ($data as $key => $value) {
            // Only update valid setting keys
            if (in_array($key, [
                'app_name', 
                'app_primary_color', 
                'app_primary_hover_color', 
                'app_secondary_color',
                'login_background_image'
            ])) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Configuración actualizada',
            'settings' => Setting::all()->pluck('value', 'key'),
        ]);
    }

    public function uploadBackground(Request $request)
    {
        $request->validate([
            'background' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($request->hasFile('background')) {
            $path = $request->file('background')->store('design', 'public');
            $imageUrl = asset('storage/' . $path);
            
            Setting::updateOrCreate(
                ['key' => 'login_background_image'],
                ['value' => $imageUrl, 'group' => 'design']
            );

            return response()->json([
                'status' => 'success',
                'url' => $imageUrl
            ]);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }
}
