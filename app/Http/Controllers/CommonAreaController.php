<?php

namespace App\Http\Controllers;

use App\Models\CommonArea;
use Illuminate\Http\Request;

class CommonAreaController extends Controller
{
    public function index()
    {
        return response()->json(['data' => CommonArea::all()]);
    }

    public function store(Request $request)
    {
        if (!$request->user()->hasRole(['master', 'admin'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'time_limit' => 'required|integer|min:1',
            'time_unit'  => 'required|in:horas,dias,semanas,mes,año',
            'max_people' => 'required|integer|min:1',
            'fee_type'   => 'nullable|in:none,per_person,per_time',
            'fee_amount' => 'nullable|numeric|min:0',
        ]);

        $area = CommonArea::create($validated);
        return response()->json(['status' => 'success', 'data' => $area]);
    }

    public function update(Request $request, $id)
    {
        if (!$request->user()->hasRole(['master', 'admin'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'time_limit' => 'required|integer|min:1',
            'time_unit'  => 'required|in:horas,dias,semanas,mes,año',
            'max_people' => 'required|integer|min:1',
            'fee_type'   => 'nullable|in:none,per_person,per_time',
            'fee_amount' => 'nullable|numeric|min:0',
        ]);

        $area = CommonArea::findOrFail($id);
        $area->update($validated);
        
        return response()->json(['status' => 'success', 'data' => $area]);
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->hasRole(['master', 'admin'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        CommonArea::findOrFail($id)->delete();
        return response()->json(['status' => 'success']);
    }
}
