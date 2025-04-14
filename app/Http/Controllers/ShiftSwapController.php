<?php
namespace App\Http\Controllers;

use App\Http\Requests\ShiftSwap\ApproveShiftSwapRequest;
use App\Http\Requests\ShiftSwap\CreateShiftSwapRequest;
use App\Services\ShiftSwapService;
use Illuminate\Support\Facades\Auth;

class ShiftSwapController extends Controller
{
    protected $shiftSwapRequestService;

    public function __construct(ShiftSwapService $shiftSwapRequestService)
    {
        $this->shiftSwapRequestService = $shiftSwapRequestService;
    }

    // Create a shift swap request
    public function requestSwap(CreateShiftSwapRequest $request)
    {
        $validated = $request->validated();
        $swapRequest = $this->shiftSwapRequestService->createRequest($validated);

        return response()->json($swapRequest, 201);
    }

    // Fetch all shift swap requests
    public function getRequests()
    {
        $requests = $this->shiftSwapRequestService->getAllRequests();
        return response()->json($requests, 200);
    }

    // Fetch requests for the logged-in user
    public function getUserRequests()
    {
        $userId = Auth::id();
        $requests = $this->shiftSwapRequestService->getUserRequests($userId);
        return response()->json($requests, 200);
    }

    // Approve a shift swap request
    public function approveRequest(ApproveShiftSwapRequest $request, $id)
    {
        $isApproved = $this->shiftSwapRequestService->approveRequest($id);

        if ($isApproved) {
            return response()->json(['message' => 'Shift swap request approved successfully.'], 200);
        }

        return response()->json(['error' => 'Failed to approve shift swap request.'], 400);
    }

    // Reject a shift swap request
    public function rejectRequest($id)
    {
        $isRejected = $this->shiftSwapRequestService->rejectRequest($id);

        if ($isRejected) {
            return response()->json(['message' => 'Shift swap request rejected successfully.'], 200);
        }

        return response()->json(['error' => 'Failed to reject shift swap request.'], 400);
    }
}
