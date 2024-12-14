<?php

namespace App\Http\Controllers;

use App\Models\ShiftLabel;
use Illuminate\Http\Request;

class ShiftLabelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $shopId)
    {
        $shiftLabels = ShiftLabel::where('shop_id', $shopId)->get();

        return response()->json($shiftLabels);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $shiftLabel = ShiftLabel::create([
            'shop_id' => $validated['shop_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return response()->json($shiftLabel, 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(ShiftLabel $shiftLabel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $shiftLabel = ShiftLabel::findOrFail($id);

        // Ensure the logged-in admin owns the shop
        if ($shiftLabel->creator->id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $shiftLabel->update($validated);

        return response()->json($shiftLabel);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $shiftLabel = ShiftLabel::findOrFail($id);

        // Ensure the logged-in admin owns the shop
        if ($shiftLabel->creator->id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $shiftLabel->delete();

        return response()->json(['message' => 'Shift label deleted successfully']);
    }

}
