<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\ShiftSwapRequest;
use Illuminate\Support\Facades\DB;

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
        $currentUser = auth()->user();

        $userId = $currentUser->id;

        if ($currentUser->role === User::USER_ROLE_MANAGER || $currentUser->role === User::USER_ROLE_SYSTEM_ADMIN) {
            $userIds = User::where('employer', $userId)->pluck('id')->toArray();
            $userIds[] = $currentUser->id;
            $requests = ShiftSwapRequest::whereIn('requester_id', $userIds)
                ->orWhereIn('requested_id', $userIds)
                ->with(['requester', 'requested', 'shiftLabel'])
                ->get();
            return response()->json($requests, 200);

        }
        $requests = ShiftSwapRequest::where('requester_id', $userId)
            ->orWhere('requested_id', $userId)
            ->with(['requester', 'requested', 'shiftLabel'])
            ->get();

        return response()->json($requests, 200);
    }

    // Approve a shift swap request
    public function approveRequest($id)
    {
        DB::beginTransaction(); // Start a transaction

        try {
            $swapRequest = ShiftSwapRequest::findOrFail($id);

            // Ensure the request is still pending
            if ($swapRequest->status !== 0) {
                return response()->json(['error' => 'Request is not pending.'], 400);
            }

            // Get the shift details of the requester and requested employees
            $shopId = $swapRequest->shop_id;
            $shiftDate = $swapRequest->shift_date;
            $shiftLabelId = $swapRequest->shift_label_id;
            $requesterId = $swapRequest->requester_id;
            $requestedId = $swapRequest->requested_id;

            // Find the shift for the requester's shift
            $shift = Shift::query()
                ->where('shop_id', $shopId)
                ->where('date', $shiftDate)
                ->firstOrFail();

            // Decode the JSON shift data to manipulate it
            $shiftData = $shift->shift_data;

            $index = array_search(true, array_map(function ($data) use ($shiftLabelId, $requesterId) {
                return $data['label']['id'] === $shiftLabelId && $data['userId'] === $requesterId;
            }, $shiftData));

            if ($index !== false) {
                $shiftData[$index]['userId'] = $requestedId;
                $shiftData[$index]['username'] = User::findOrFail($requestedId)->name;
            }

            // Save the updated shift data back to the database
            $shift->shift_data = $shiftData;
            $shift->save();

            // Update the status of the swap request to "approved"
            $swapRequest->status = 1; // Approved
            $swapRequest->save();

            DB::commit(); // Commit the transaction

            return response()->json(['message' => 'Shift swap request approved and shifts updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback the transaction in case of error

            return response()->json(['error' => 'Failed to approve shift swap request.', 'message' => $e->getMessage()], 500);
        }
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
