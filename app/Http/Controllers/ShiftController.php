<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\Rule;
use App\Models\Shift;
use App\Models\ShiftLabel;
use App\Models\Shop;
use App\Models\User;
use App\Models\UserOffDay;
use App\Services\HistoryService;
use App\Services\ShiftService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShiftController extends Controller
{
    protected ShiftService $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    public function index(Request $request)
    {
        $request->validate([
            'shop_id' => 'sometimes|exists:shops,id',
        ]);

        $shifts = $this->shiftService->getAllShifts($request);
        $formattedShifts = $this->shiftService->formatShiftsForResponse($shifts);

        return response()->json($formattedShifts);
    }

    public function removeShift(Request $request)
    {
        $validatedData = $request->validate([
            'shopId' => 'required|integer|exists:shops,id',
            'date' => 'required|date',
            'shiftData' => 'array',
        ]);
        $currentUser = auth()->user();
        if (!UserController::CheckIfUserCanManageThisShop($currentUser->id, $validatedData['shopId'])) {
            Log::error('You cannot manage this shop', $validatedData);
            return response()->json(['error' => 'You cannot manage this shop'], 403);
        }

        $result = $this->shiftService->removeShift($validatedData);

        return response()->json(
            $result['success'] ? ['message' => $result['message']] : ['error' => $result['message'], 'details' => $result['details'] ?? null],
            $result['status']
        );
    }

    public function employeeShifts(Request $request)
    {
        $result = $this->shiftService->getEmployeeShifts($request);

        if (!$result['success']) {
            return response()->json(['error' => $result['message']], $result['status']);
        }

        return response()->json($result['data']);
    }

    public function store(Request $request)
    {
        $validatedRequest = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'date' => 'required|date',
            'shift_data' => 'required|array',
        ]);

        $result = $this->shiftService->storeShift($validatedRequest);

        if (!$result['success']) {
            return response()->json(['error' => $result['message']], $result['status']);
        }

        return response()->json($result['data'], $result['status']);
    }

    public function show($id)
    {
        return Shift::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $validatedRequest = $request->validate([
            'shift_data' => 'sometimes|array',
        ]);
        
        $result = $this->shiftService->updateShift($id, $validatedRequest);
        
        if (!$result['success']) {
            return response()->json(['error' => $result['message']], $result['status']);
        }
        
        return response()->json($result['data']);
    }

    public function destroy($id)
    {
        $shift = Shift::findOrFail($id);
        $shift->delete();

        return response()->json(['message' => 'Shift deleted'], 200);
    }

    public function auto(Request $request)
    {
        $validatedData = $request->validate([
            'dateFrom' => ['required', 'date', 'before_or_equal:dateTo'], // Mandatory and must be a valid date
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],  // Mandatory and must be a valid date
            'shopId' => 'required|integer|exists:shops,id',
        ]);

        $result = $this->shiftService->autoAssignShifts($validatedData);

        return response()->json(
            $result['success'] ? ['message' => $result['message']] : ['error' => $result['message']],
            $result['status']
        );
    }
}
