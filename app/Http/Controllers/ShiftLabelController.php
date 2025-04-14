<?php
namespace App\Http\Controllers;

use App\Http\Requests\ShiftLabel\ShiftLabelRequest;
use App\Models\Shop;
use App\Services\ShiftLabelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\History;
use App\Services\HistoryService;

class ShiftLabelController extends Controller
{
    protected ShiftLabelService $shiftLabelService;

    public function __construct(ShiftLabelService $shiftLabelService)
    {
        $this->shiftLabelService = $shiftLabelService;
    }

    public function index(ShiftLabelRequest $request)
    {
        $currentUser = auth()->user();
        if (!UserController::CheckIfUserCanManageThisShop($currentUser->id, $request->shop_id)) {
            return response()->json(['error' => 'You cannot manage this shop'], 403);
        }

        $shiftLabels = $this->shiftLabelService->getShiftLabelsByShopId($request->shop_id);
        return response()->json($shiftLabels);
    }

    public function getAllActive(ShiftLabelRequest $request)
    {
        $currentUser = auth()->user();
        if (!UserController::CheckIfUserCanManageThisShop($currentUser->id, $request->shop_id)) {
            return response()->json(['error' => 'You cannot manage this shop'], 403);
        }

        $shiftLabels = $this->shiftLabelService->getActiveShiftLabelsByShopId($request->shop_id);
        return response()->json($shiftLabels);
    }

    public function store(ShiftLabelRequest $request)
    {
        $validated = $request->validated();
        $validated['applicable_days'] = $validated['applicable_days'] ?? ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

        $currentUser = auth()->user();
        if (!Shop::where('id', $request->shop_id)->where('owner', $currentUser->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $shiftLabel = $this->shiftLabelService->createShiftLabel([
            'shop_id' => $request->shop_id,
            'user_id' => $currentUser->id,
            'label' => $request->label,
            'default_duration_minutes' => $request->default_duration_minutes,
            'applicable_days' => $validated['applicable_days'],
        ]);

        HistoryService::log(History::ADD_LABEL, $validated);
        return response()->json($shiftLabel, 201);
    }

    public function update(ShiftLabelRequest $request, $id)
    {
        $shiftLabel = $this->shiftLabelService->findById($id);
        if (!$shiftLabel) {
            return response()->json(['error' => 'Shift label not found'], 404);
        }

        $currentUser = auth()->user();
        if (!UserController::CheckIfUserCanManageThisShop($currentUser->id, $shiftLabel->shop_id)) {
            Log::error('You cannot manage this shop', []);
            return response()->json(['error' => 'You cannot manage this shop'], 403);
        }

        $validated = $request->validated();
        $this->shiftLabelService->updateShiftLabel($id, $validated);

        HistoryService::log(History::UPDATE_LABEL, $validated);
        return response()->json($shiftLabel);
    }

    public function destroy($id)
    {
        $shiftLabel = $this->shiftLabelService->deleteShiftLabel($id);
        if (!$shiftLabel) {
            return response()->json(['error' => 'Shift label not found'], 404);
        }

        HistoryService::log(History::REMOVE_LABEL, ['Shift_label_id' => $id]);
        return response()->json(['message' => 'Shift label deleted successfully']);
    }

    public function updateActiveStatus(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $shiftLabel = $this->shiftLabelService->findById($id);
        if (!$shiftLabel) {
            return response()->json(['error' => 'Shift label not found'], 404);
        }

        $shiftLabel->is_active = $request->is_active;
        $shiftLabel->save();

        return response()->json($shiftLabel);
    }
}
