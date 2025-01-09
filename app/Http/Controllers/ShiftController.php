<?php
namespace App\Http\Controllers;

use App\Models\Rule;
use App\Models\Shift;
use App\Models\ShiftLabel;
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

    public function removeShift(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'shopId' => 'required|integer|exists:shops,id',
            'date' => 'required|date',
            'shiftData' => 'array',
        ]);

        try {
            // Find the shift by shopId and date using Eloquent
            $shift = Shift::where('shop_id', $validatedData['shopId'])
                ->where('date', $validatedData['date'])
                ->first();

            // If no shift is found, return an error response
            if (!$shift) {
                return response()->json(['error' => 'Shift not found.'], 404);
            }

            // Update the shift_data column
            $shift->shift_data = $validatedData['shiftData'];
            $shift->save();

            return response()->json(['message' => 'Shift updated successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while updating the shift.', 'details' => $e->getMessage()], 500);
        }
    }

    public function employeeShifts(Request $request)
    {
        $currentUser = auth()->user();

        // Fetch shops owned by the current user, excluding shops with state = 0
        $userShopsOwned = Shop::where('owner', $currentUser->id)
            ->where('state', 1)
            ->get(['id', 'name']);

        // Fetch shops where the current user is a manager
        $userShopsManaged = Shop::join('shop_user', 'shops.id', '=', 'shop_user.shop_id')
            ->where('shop_user.user_id', $currentUser->id)
            ->where('shop_user.role', Shop::SHOP_USER_ROLE_MANAGER)
            ->where('shops.state', 1)
            ->get(['shops.id', 'shops.name']);

        // Combine owned and managed shops
        $allUserShops = $userShopsOwned->merge($userShopsManaged);

        if ($allUserShops->isEmpty()) {
            return response()->json([]);
        }

        // Map shop IDs to their names for quick lookup
        $shopMap = $allUserShops->pluck('name', 'id'); // [shop_id => shop_name]

        // Fetch shifts for the user's shops
        $shiftsQuery = Shift::query()->whereIn('shop_id', $shopMap->keys());
        if ($request->has('shop_id')) {
            $shiftsQuery->where('shop_id', $request->shop_id);
        }

        $shifts = $shiftsQuery->get();

        // Fetch user information for processing
        $userMap = User::pluck('name', 'id'); // [user_id => username]

        // Validate dateFrom and dateTo, or set defaults
        $dateFrom = $request->has('dateFrom') ? Carbon::parse($request->dateFrom) : Carbon::now()->startOfWeek(Carbon::MONDAY);
        $dateTo = $request->has('dateTo') ? Carbon::parse($request->dateTo) : $dateFrom->copy()->addDays(34);

        // Ensure dateTo is not earlier than dateFrom
        if ($dateTo->lt($dateFrom)) {
            return response()->json(['error' => 'dateTo cannot be earlier than dateFrom'], 400);
        }

        // Generate all dates in the range
        $allDates = [];
        for ($date = $dateFrom->copy(); $date->lte($dateTo); $date->addDay()) {
            $allDates[] = $date->toDateString();
        }

        // Group shifts by shop and then by date
        $shiftsByShop = $shifts->groupBy('shop_id')->map(function ($shiftsInShop) use ($userMap) {
            return $shiftsInShop->groupBy('date')->mapWithKeys(function ($shiftsOnDate, $date) use ($userMap) {
                return [
                    $date => $shiftsOnDate->map(function ($shift) use ($userMap) {
                        return collect($shift->shift_data)->map(function ($data) use ($userMap) {
                            return array_merge($data, [
                                'username' => $userMap->get($data['userId'], 'Unassigned'),
                            ]);
                        })->toArray();
                    })->flatten(1)->toArray(),
                ];
            });
        });

        // Build the response
        $fullShiftsByShop = $shopMap->map(function ($shopName, $shopId) use ($shiftsByShop, $allDates) {
            $datesWithShifts = $shiftsByShop->get($shopId, collect());

            // Include all dates, even if they don't have shifts
            $datesData = collect($allDates)->mapWithKeys(function ($date) use ($datesWithShifts) {
                return [
                    $date => $datesWithShifts->get($date, []),
                ];
            });

            return array_merge([
                'shop_id' => $shopId,
                'shop_name' => $shopName,
            ], $datesData->toArray());
        });

        return response()->json($fullShiftsByShop->values());
    }

    public function employeeShifts3(Request $request)
    {
        $currentUser = auth()->user();

        // Fetch shops owned by the current user, excluding shops with state = 0
        $userShopsOwned = Shop::where('owner', $currentUser->id)
            ->where('state', 1)
            ->get(['id', 'name']);

        // Fetch shops where the current user is a manager
        $userShopsManaged = Shop::join('shop_user', 'shops.id', '=', 'shop_user.shop_id')
            ->where('shop_user.user_id', $currentUser->id)
            ->where('shop_user.role', Shop::SHOP_USER_ROLE_MANAGER)
            ->where('shops.state', 1)
            ->get(['shops.id', 'shops.name']);

        // Combine owned and managed shops
        $allUserShops = $userShopsOwned->merge($userShopsManaged);

        if ($allUserShops->isEmpty()) {
            return response()->json([]);
        }

        // Map shop IDs to their names for quick lookup
        $shopMap = $allUserShops->pluck('name', 'id'); // [shop_id => shop_name]

        // Fetch shifts for the user's shops
        $shiftsQuery = Shift::query()->whereIn('shop_id', $shopMap->keys());
        if ($request->has('shop_id')) {
            $shiftsQuery->where('shop_id', $request->shop_id);
        }

        $shifts = $shiftsQuery->get();

        // Fetch user information for processing
        $userMap = User::pluck('name', 'id'); // [user_id => username]

        // Group shifts by shop
        $shiftsByShop = $shifts->groupBy('shop_id')
            ->map(function ($shiftsInShop) use ($userMap) {
                return $shiftsInShop->groupBy('date')->mapWithKeys(function ($shiftsOnDate, $date) use ($userMap) {
                    return [
                        $date => $shiftsOnDate->map(function ($shift) use ($userMap) {
                            return collect($shift->shift_data)->map(function ($data) use ($userMap) {
                                return array_merge($data, [
                                    'username' => $userMap->get($data['userId'], 'Unassigned'),
                                ]);
                            })->toArray();
                        })->flatten(1)->toArray(),
                    ];
                });
            });

        // Generate all dates for the next 35 days
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $allDates = [];
        for ($i = 0; $i < 35; $i++) {
            $allDates[] = $startOfWeek->copy()->addDays($i)->toDateString();
        }

        // Flatten structure and include all dates at the same level
        $fullShiftsByShop = $shopMap->map(function ($shopName, $shopId) use ($shiftsByShop, $allDates) {
            $datesWithShifts = $shiftsByShop->get($shopId, collect());

            // Flatten dates and shift data into the same level
            $datesData = collect($allDates)->map(function ($date) use ($datesWithShifts) {
                return [
                    'date' => $date,
                    'shift_data' => $datesWithShifts->get($date, []),
                ];
            });

            return [
                'shop_id' => $shopId,
                'shop_name' => $shopName,
                'dates' => $datesData,
            ];
        });

        return response()->json($fullShiftsByShop->values());
    }

    public function employeeShifts2(Request $request)
    {
        $currentUser = auth()->user();

        // Fetch shops owned by the current user, excluding shops with state = 0
        $userShopsOwned = Shop::where('owner', $currentUser->id)
            ->where('state', 1)
            ->get(['id', 'name']);

        // Fetch shops where the current user is a manager
        $userShopsManaged = Shop::join('shop_user', 'shops.id', '=', 'shop_user.shop_id')
            ->where('shop_user.user_id', $currentUser->id)
            ->where('shop_user.role', Shop::SHOP_USER_ROLE_MANAGER)
            ->where('shops.state', 1)
            ->get(['shops.id', 'shops.name']);

        // Combine owned and managed shops
        $allUserShops = $userShopsOwned->merge($userShopsManaged);

        if ($allUserShops->isEmpty()) {
            return response()->json([]);
        }

        // Map shop IDs to their names for quick lookup
        $shopMap = $allUserShops->pluck('name', 'id'); // [shop_id => shop_name]

        // Fetch shifts for the user's shops
        $shiftsQuery = Shift::query()->whereIn('shop_id', $shopMap->keys());
        if ($request->has('shop_id')) {
            $shiftsQuery->where('shop_id', $request->shop_id);
        }

        $shifts = $shiftsQuery->get();

        // Fetch user information for processing
        $userMap = User::pluck('name', 'id'); // [user_id => username]

        // Organize shifts by shop
        $shiftsByShop = $shifts->groupBy('shop_id')
            ->map(function ($shiftsInShop) use ($userMap) {
                return $shiftsInShop->groupBy('date')->mapWithKeys(function ($shiftsOnDate, $date) use ($userMap) {
                    return [
                        $date => $shiftsOnDate->map(function ($shift) use ($userMap) {
                            return collect($shift->shift_data)->map(function ($data) use ($userMap) {
                                return array_merge($data, [
                                    'username' => $userMap->get($data['userId'], 'Unassigned'),
                                ]);
                            })->toArray();
                        })->flatten(1)->toArray(),
                    ];
                });
            });

        // Generate all dates for the next 35 days
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $allDates = [];
        for ($i = 0; $i < 35; $i++) {
            $allDates[] = $startOfWeek->copy()->addDays($i)->toDateString();
        }

        // Ensure all dates appear under each shop, even if no shifts exist
        $fullShiftsByShop = $shopMap->map(function ($shopName, $shopId) use ($shiftsByShop, $allDates) {
            $datesWithShifts = $shiftsByShop->get($shopId, collect());

            $shiftsByDate = collect($allDates)->mapWithKeys(function ($date) use ($datesWithShifts) {
                return [
                    $date => $datesWithShifts->get($date, []),
                ];
            });

            return [
                'shop_id' => $shopId,
                'shop_name' => $shopName,
                'shifts_by_date' => $shiftsByDate,
            ];
        });

        return response()->json($fullShiftsByShop->values());
    }

    public function employeeShiftsOld(Request $request)
    {
        $currentUser = auth()->user();

        // Fetch shops owned by the current user, excluding shops with state = 0
        $userShopsOwned = Shop::where('owner', $currentUser->id)
            ->where('state', 1)
            ->get(['id', 'name']);

        // Fetch shops where the current user is a manager
        $userShopsManaged = Shop::join('shop_user', 'shops.id', '=', 'shop_user.shop_id')
            ->where('shop_user.user_id', $currentUser->id)
            ->where('shop_user.role', Shop::SHOP_USER_ROLE_MANAGER)
            ->where('shops.state', 1)
            ->get(['shops.id', 'shops.name']);

        // Combine owned and managed shops
        $allUserShops = $userShopsOwned->merge($userShopsManaged);

        if ($allUserShops->isEmpty()) {
            return response()->json([]);
        }

        // Map shop IDs to their names for quick lookup
        $shopMap = $allUserShops->pluck('name', 'id'); // [shop_id => shop_name]

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
        for ($i = 0; $i < 35; $i++) {
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
        $validatedRequest = $validRequest = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'date' => 'required|date',
            'shift_data' => 'required|array',
        ]);
        $previousShiftInDb = Shift::query()->where(
            [
                'shop_id'=> $validatedRequest['shop_id'],
                'date'   => $validatedRequest['date'],
            ]
        )->first();
        if($previousShiftInDb){
            $shiftData = array_merge($previousShiftInDb['shift_data'], $validatedRequest['shift_data']);
            foreach ($shiftData as $key => $shift) {
                if ($shift['userId'] === 0) {
                    unset($shiftData[$key]);
                }
            }
            $previousShiftInDb->shift_data = $shiftData;
            $previousShiftInDb->save();
            return response()->json($previousShiftInDb, 201);
        }

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
        $previousShiftInDb = Shift::findOrFail($id);

        $validatedRequest = $request->validate([
            'shift_data' => 'sometimes|array',
        ]);

        foreach ($validatedRequest['shift_data'] as $key => $shift) {
            if ($shift['userId'] === 0) {
                unset($validatedRequest['shift_data'][$key]);
            }
        }
        $previousShiftInDb->shift_data = $validatedRequest['shift_data'];
        $previousShiftInDb->save();


//        $shift->update($request->all());

        return response()->json($previousShiftInDb);
    }

    // Delete a shift
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
        ]);

        $dateFrom = Carbon::parse($validatedData['dateFrom']);
        $dateTo = Carbon::parse($validatedData['dateTo']);

        $currentUser = auth()->user();

        // Get all shop IDs owned by the current user
        $shopIds = Shop::where('owner', $currentUser->id)->pluck('id')->toArray();

        // Get flag to avoid assigning multiple shifts to the same employee per day
        $avoidMultipleShiftsPerDay = $request->get('avoidMultipleShifts', false);

        foreach ($shopIds as $shopId) {
            // Load rules for the shop
            $rules = Rule::where('shop_id', $shopId)->get()->groupBy('employee_id');

            // Fetch all shift labels for the shop
            $shiftLabels = ShiftLabel::where('shop_id', $shopId)->get();

            // Fetch all employees of the shop
            $shop = Shop::findOrFail($shopId);
            $employees = $shop->users;

            if ($employees->isEmpty() || $shiftLabels->isEmpty()) {
                continue;
            }

            $dailyAssignments = []; // Track daily assignments for avoiding multiple shifts per day

            // Loop through days
            $i = 0;

            for ($date = $dateFrom; $date->lte($dateTo); $date->addDay()) {
                $i++;
                $dayName = $date->format('l'); // E.g., "Monday"
                $dateString = $date->toDateString();
                $shiftData = [];
                // Shuffle employees to randomize assignment

                // Assign shifts for each label
                foreach ($shiftLabels as $label) {
                    $shuffledEmployees = $employees->shuffle();

                    foreach ($shuffledEmployees as $employee) {
                        // Check rules for the employee
                        $employeeRules = $rules->get($employee->id, []);
                        // Avoid assigning multiple shifts per day if the flag is true
                        if ($avoidMultipleShiftsPerDay && isset($dailyAssignments[$i][$employee->id])) {
                            continue;
                        }

                        if ($this->violatesRules($label, $dayName, $employeeRules)) {
                            continue; // Skip employee if they violate any rule
                        }

                        // Assign the shift to the employee
                        $shiftData[] = [
                            'label' => [
                                "id" => $label->id,
                                "name" => $label->label
                            ],
                            'userId' => $employee->id,
                            'username' => $employee->name,
                            'duration_minutes' => $label->default_duration_minutes ?? 0,
                        ];
                        // Track daily assignments
                        $dailyAssignments[$i][$employee->id] = true;

                        break; // Move to the next shift label once assigned
                    }
                }

                // Check if a shift already exists for this shop and date
                $existingShift = Shift::query()->where('shop_id', $shopId)->where('date', $dateString)->first();

                if ($existingShift) {
                    $existingShift->shift_data = $shiftData;
                    $existingShift->save();
                } else {
                    // Create a new shift
                    Shift::create([
                        'shop_id' => $shopId,
                        'date' => $dateString,
                        'shift_data' => $shiftData,
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Shifts auto-assigned successfully'], 201);
    }

    private function violatesRules($label, $dayName, $employeeRules)
    {
        foreach ($employeeRules as $rule) {
            if ($rule->shop_id !== $label->shop_id) {
                continue; // Skip rules for other shops
            }

//            switch ($rule->rule_type) {
//                case 'exclude_label':
//                    if ($rule->rule_data == $label->id) {
//                        return true;
//                    }
//                    break;
//
//                case 'exclude_days':
//                    if (in_array($dayName, $rule->rule_data['days'])) {
//                        return true;
//                    }
//                    break;
//            }
        }

        return false;
    }


}
