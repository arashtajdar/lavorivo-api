<?php

namespace App\Http\Controllers;

use App\Models\ShiftLabel;
use App\Models\Shop;
use Illuminate\Http\Request;

class ShiftLabelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
        ]);

        $currentUser = auth()->user();

        // Ensure the user owns the shop
        if (!Shop::where('id', $request->shop_id)->where('owner', $currentUser->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $shiftLabels = ShiftLabel::where('shop_id', $request->shop_id)->get();
        return response()->json($shiftLabels);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'label' => 'required|string|max:255',
            'default_duration_minutes' => 'nullable|integer|min:1',
        ]);

        $currentUser = auth()->user();
//dd($currentUser->shops()->where('shop_id', $request->shop_id)->toSql());
        // Ensure the user owns the shop
        if (!Shop::where('id', $request->shop_id)->where('owner', $currentUser->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }


        $shiftLabel = ShiftLabel::create([
            'shop_id' => $request->shop_id,
            'user_id' => $currentUser->id,
            'label' => $request->label,
            'default_duration_minutes' => $request->default_duration_minutes,
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
     * Show the form for editing the specified resource.
     */
    public function edit(ShiftLabel $shiftLabel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $shiftLabel = ShiftLabel::findOrFail($id);

        // Ensure the user is authorized to update this shift label
        $currentUser = auth()->user();
        if (!Shop::where('id', $shiftLabel->shop_id)->where('owner', $currentUser->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'label' => 'required|string|max:255',
            'default_duration_minutes' => 'nullable|integer|min:1',
        ]);

        $shiftLabel->update($request->only(['label', 'default_duration_minutes']));

        return response()->json($shiftLabel);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $shiftLabel = ShiftLabel::findOrFail($id);

        $shiftLabel->delete();

        return response()->json(['message' => 'Shift label deleted successfully']);
    }

}
