<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShiftSwapRequest;

class ShiftSwapController extends Controller
{
    // Create a shift swap request
    public function requestSwap(Request $request)
    {
        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'shift_label_id' => 'required|exists:shift_labels,id',
            'shift_date' => 'required|date',
            'requester_id' => 'required|exists:users,id',
            'requested_id' => 'required|exists:users,id',
        ]);

        $swapRequest = ShiftSwapRequest::create($validated);

        return response()->json($swapRequest, 201);
    }

    // Fetch all shift swap requests
    public function getRequests()
    {
        $requests = ShiftSwapRequest::with(['requester', 'requested', 'shiftLabel'])->get();
        return response()->json($requests, 200);
    }

    // Fetch requests for the logged-in user
    public function getUserRequests()
    {
        $userId = auth()->id();
        $requests = ShiftSwapRequest::where('requester_id', $userId)
            ->orWhere('requested_id', $userId)
            ->with(['requester', 'requested', 'shiftLabel'])
            ->get();

        return response()->json($requests, 200);
    }

    // Approve a shift swap request
    public function approveRequest($id)
    {
        $swapRequest = ShiftSwapRequest::findOrFail($id);

        if ($swapRequest->status !== 0) {
            return response()->json(['error' => 'Request is not pending.'], 400);
        }

        $swapRequest->status = 1; // Approved
        $swapRequest->save();

        return response()->json(['message' => 'Shift swap request approved successfully.'], 200);
    }

    // Reject a shift swap request
    public function rejectRequest($id)
    {
        $swapRequest = ShiftSwapRequest::findOrFail($id);

        if ($swapRequest->status !== 0) {
            return response()->json(['error' => 'Request is not pending.'], 400);
        }

        $swapRequest->status = 2; // Rejected
        $swapRequest->save();

        return response()->json(['message' => 'Shift swap request rejected successfully.'], 200);
    }
}
