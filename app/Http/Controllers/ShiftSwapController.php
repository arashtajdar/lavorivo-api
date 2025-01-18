<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShiftSwapRequest; // Import the model
use App\Models\ShiftLabel; // Import for validation
use App\Models\User; // Import for user validation

class ShiftSwapController extends Controller
{
    public function requestSwap(Request $request)
    {
        // Validate the request input
        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id', // Validate shop_id
            'shift_label_id' => 'required|exists:shift_labels,id',
            'shift_date' => 'required|date|after_or_equal:today',
            'requester_id' => 'required|exists:users,id',
            'requested_id' => 'required|exists:users,id|different:requester_id',
        ]);

        // Create the shift swap request
        $swapRequest = ShiftSwapRequest::create([
            'shop_id' => $validated['shop_id'],
            'shift_label_id' => $validated['shift_label_id'],
            'shift_date' => $validated['shift_date'],
            'requester_id' => $validated['requester_id'],
            'requested_id' => $validated['requested_id'],
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Shift swap request created successfully.', 'data' => $swapRequest], 201);
    }
}
