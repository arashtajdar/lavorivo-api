<?php
namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ShiftController extends Controller
{
    // List all shifts

    public function index(Request $request)
    {
        // Validate optional shop_id query parameter
        $request->validate([
            'shop_id' => 'sometimes|exists:shops,id',
        ]);

        // Fetch shifts, optionally filtered by shop_id
        $shiftsQuery = Shift::query();
        if ($request->has('shop_id')) {
            $shiftsQuery->where('shop_id', $request->shop_id);
        }

        $shifts = $shiftsQuery->get();
        $formattedShifts = $shifts->map(function ($shift) {
            return [
                'id' => $shift->id,
                'shop_id' => $shift->shop_id,
                'date' => $shift->date,
                'shift_data' => $shift->shift_data,
            ];
        });

        return response()->json($formattedShifts);
    }

    public function employeeShifts(Request $request)
    {
        $currentUser = auth()->user();
        $userShopsOwned = Shop::where('owner', $currentUser->id)->get(['id', 'name']);

        if ($userShopsOwned->isEmpty()) {
            return response()->json([]);
        }

        // Map shop IDs to their names for quick lookup
        $shopMap = $userShopsOwned->pluck('name', 'id'); // [shop_id => shop_name]

        // Fetch shifts for the user's shops
        $shiftsQuery = Shift::query()->whereIn('shop_id', $shopMap->keys());
        if ($request->has('shop_id')) {
            $shiftsQuery->where('shop_id', $request->shop_id);
        }

        $shifts = $shiftsQuery->get();

        // Fetch user information for processing
        $userMap = User::pluck('name', 'id'); // [user_id => username]

        // Organize shifts by date
        $shiftsByDate = $shifts->groupBy('date')
            ->map(function ($shiftsOnDate) use ($userMap, $shopMap) {
                // Group shifts by shop_id
                return $shiftsOnDate->groupBy('shop_id')->map(function ($shiftsByShop, $shopId) use ($userMap, $shopMap) {
                    return [
                        'id' => $shiftsByShop->first()->id,
                        'shop_id' => $shopId,
                        'shop_name' => $shopMap->get($shopId, 'Unknown Shop'),
                        'shift_data' => $shiftsByShop->map(function ($shift) use ($userMap) {
                            return collect($shift->shift_data)->map(function ($data) use ($userMap) {
                                return array_merge($data, [
                                    'username' => $userMap->get($data['userId'], 'Unassigned'),
                                ]);
                            })->toArray();
                        })->flatten(1)->toArray(),
                    ];
                });
            });

        // Ensure all shops appear under each date, even if no shifts exist
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $allDates = [];
        for ($i = 0; $i < 28; $i++) {
            $allDates[] = $startOfWeek->copy()->addDays($i)->toDateString();
        }

        $fullShiftsByDate = collect($allDates)->mapWithKeys(function ($date) use ($shiftsByDate, $shopMap) {
            // Initialize with all shops and empty shift_data
            $shopsWithShifts = $shiftsByDate->get($date, collect());
            $shopsWithShifts = $shopsWithShifts->toArray();

            $shops = $shopMap->map(function ($shopName, $shopId) use ($shopsWithShifts) {
                $existingShop = collect($shopsWithShifts)->firstWhere('shop_id', $shopId);

                return $existingShop ?? [
                    'id' => null,
                    'shop_id' => $shopId,
                    'shop_name' => $shopName,
                    'shift_data' => [],
                ];
            });

            return [
                $date => $shops->values()->toArray(),
            ];
        });

        return response()->json($fullShiftsByDate);
    }


    // Create a new shift
    public function store(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'date' => 'required|date',
            'shift_data' => 'required|array',
        ]);

        $shift = Shift::create($request->all());

        return response()->json($shift, 201);
    }

    // Show a specific shift
    public function show($id)
    {
        return Shift::findOrFail($id);
    }

    // Update a shift
    public function update(Request $request, $id)
    {
        $shift = Shift::findOrFail($id);

        $request->validate([
            'shift_data' => 'sometimes|array',
        ]);

        $shift->update($request->all());

        return response()->json($shift);
    }

    // Delete a shift
    public function destroy($id)
    {
        $shift = Shift::findOrFail($id);
        $shift->delete();

        return response()->json(['message' => 'Shift deleted'], 200);
    }

    // Add to ShiftController or create a new route

}
