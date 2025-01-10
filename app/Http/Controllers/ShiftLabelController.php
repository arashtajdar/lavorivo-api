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


    public function getAllShiftLabels()
    {
        $currentUser = auth()->user();

        // Get all shops owned by the current user with eager loading of shift labels
        $ownedShops = Shop::where('owner', $currentUser->id)
            ->with('shiftLabels') // assuming there's a relationship 'shiftLabels' on Shop model
            ->get();

        // Group the shift labels by shop_id
        $shiftLabels = $ownedShops->flatMap(function ($shop) {
            return $shop->shiftLabels->groupBy('shop_id');
        });

        return response()->json($shiftLabels);
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
        $validated['applicable_days'] = $validated['applicable_days'] ?? ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
        $currentUser = auth()->user();
        // Ensure the user owns the shop
        if (!Shop::where('id', $request->shop_id)->where('owner', $currentUser->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }


        $shiftLabel = ShiftLabel::create([
            'shop_id' => $request->shop_id,
            'user_id' => $currentUser->id,
            'label' => $request->label,
            'default_duration_minutes' => $request->default_duration_minutes,
            'applicable_days' => $validated['applicable_days'],
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

        $validated = $request->validate([
            'label' => 'string|max:255',
            'default_duration_minutes' => 'nullable|integer',
            'applicable_days' => 'nullable',
        ]);

        $shiftLabel->update($validated);

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

    public function updateActiveStatus(Request $request, $id)
    {
        // Validate the request to ensure 'is_active' is a boolean
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        // Find the shift label by its ID
        $shiftLabel = ShiftLabel::findOrFail($id);

        // Update the 'is_active' status based on the request input
        $shiftLabel->is_active = $request->is_active;

        // Save the updated status
        $shiftLabel->save();

        // Return the updated shift label
        return response()->json($shiftLabel);
    }

}
