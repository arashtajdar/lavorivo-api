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
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $currentUser = auth()->user();
        if (!UserController::CheckIfUserCanManageThisShop($currentUser->id, $validatedData['shopId'])) {
            Log::error('You cannot manage this shop', $validatedData);
            return response()->json(['error' => 'You cannot manage this shop'], 403);
        }
        try {
            // Find the shift by shopId and date using Eloquent
            $shift = Shift::where('shop_id', $validatedData['shopId'])
                ->where('date', $validatedData['date'])
                ->first();

            // If no shift is found, return an error response
            if (!$shift) {
                Log::error('Shift not found.', $validatedData);
                return response()->json(['error' => 'Shift not found.'], 404);
            }

            // Update the shift_data column
            $shift->shift_data = $validatedData['shiftData'];
            $shift->save();
            HistoryService::log(History::REMOVE_SHIFT, $validatedData);

            return response()->json(['message' => 'Shift updated successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('An error occurred while removing the shift.', $validatedData);
            return response()->json(['error' => 'An error occurred while removing the shift.', 'details' => $e->getMessage()], 500);
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
//            ->where('shop_user.role', Shop::SHOP_USER_ROLE_MANAGER)
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
        $dateTo = $request->has('dateTo') ? Carbon::parse($request->dateTo) : $dateFrom->copy()->addDays(6);

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
            $currentUser = auth()->user();
            return array_merge([
                'shop_id' => $shopId,
                'shop_name' => $shopName,
                'manager' => !!UserController::CheckIfUserCanManageThisShop($currentUser->id, $shopId),
            ], $datesData->toArray());
        });

        return response()->json($fullShiftsByShop->values());
    }

    // Create a new shift
    public function store(Request $request)
    {
        $validatedRequest = $validRequest = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'date' => 'required|date',
            'shift_data' => 'required|array',
        ]);
        $currentUser = auth()->user();
        if (!UserController::CheckIfUserCanManageThisShop($currentUser->id, $validatedRequest['shop_id'])) {
            Log::error('You cannot manage this shop', $validatedRequest);
            return response()->json(['error' => 'You cannot manage this shop'], 403);
        }
        $previousShiftInDb = Shift::query()->where(
            [
                'shop_id' => $validatedRequest['shop_id'],
                'date' => $validatedRequest['date'],
            ]
        )->first();
        if ($previousShiftInDb) {
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
        HistoryService::log(History::ADD_SHIFT, $validatedRequest);

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
        HistoryService::log(History::UPDATE_SHIFT, $validatedRequest);


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
            'shopId' => 'required|integer|exists:shops,id',
        ]);

        $dateFrom = Carbon::parse($validatedData['dateFrom']);
        $dateTo = Carbon::parse($validatedData['dateTo']);
        $shopId = $validatedData['shopId'];
        $currentUser = auth()->user();

        // Ensure user can manage this shop
        if (!UserController::CheckIfUserCanManageThisShop($currentUser->id, $shopId)) {
            Log::error('AUTO: You cannot manage this shop', $validatedData);

            return response()->json(['error' => 'You cannot manage this shop'], 403);
        }

        // Load rules for the shop
        $rules = Rule::where('shop_id', $shopId)->get()->groupBy('employee_id');

        // Fetch all shift labels for the shop
        $shiftLabels = ShiftLabel::where('shop_id', $shopId)->get();

        // Fetch all employees of the shop
        $shop = Shop::findOrFail($shopId);
        $employees = $shop->users;

        if ($employees->isEmpty()) {
            Log::error('There are no active employees for this shop!', $validatedData);

            return response()->json(['message' => 'There are no active employees for this shop!'], 400);
        }

        if ($shiftLabels->isEmpty()) {
            Log::error('There are no shift labels for this shop!', $validatedData);
            return response()->json(['message' => 'There are no shift labels for this shop!'], 400);
        }

        // Fetch all off-day records for employees within the date range
        $offDays = UserOffDay::whereIn('user_id', $employees->pluck('id'))
            ->whereBetween('off_date', [$dateFrom, $dateTo])
            ->where('status', UserOffDay::USER_OFF_DAY_STATUS_APPROVED)
            ->get()
            ->groupBy('user_id'); // Group off-days by employee ID

        $dailyAssignments = []; // Track daily assignments for avoiding multiple shifts per day

        // Loop through days
        $i = 0;
        for ($date = $dateFrom; $date->lte($dateTo); $date->addDay()) {
            $i++;
            $dayName = $date->format('l'); // E.g., "Monday"
            $dateString = $date->toDateString();
            $shiftData = [];

            // Assign shifts for each label
            foreach ($shiftLabels as $label) {
                $shuffledEmployees = $employees->shuffle();

                foreach ($shuffledEmployees as $employee) {
                    // Check if the employee has an off-day on this date
                    if (isset($offDays[$employee->id]) && $offDays[$employee->id]->contains('off_date', $dateString)) {
                        continue; // Skip employee if they have an off-day
                    }
                    if (isset($dailyAssignments[$dateString][$employee->id])) {
                        continue; // Skip if the employee already has a shift on this date
                    }
                    // Check rules for the employee
                    $employeeRules = $rules->get($employee->id, []);
                    if ($this->violatesRules($label, $dayName, $employeeRules)) {
                        continue; // Skip employee if they violate any rule
                    }

                    // Assign the shift to the employee
                    $shiftData[] = [
                        'label' => [
                            "id" => $label->id,
                            "name" => $label->label,
                        ],
                        'userId' => $employee->id,
                        'username' => $employee->name,
                        'duration_minutes' => $label->default_duration_minutes ?? 0,
                    ];

                    // Track daily assignments
                    $dailyAssignments[$dateString][$employee->id] = true;

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

        return response()->json(['message' => 'Shifts auto-assigned successfully'], 201);
    }

    private function violatesRules($label, $dayName, $employeeRules)
    {
        foreach ($employeeRules as $rule) {
            if ($rule->shop_id !== $label->shop_id) {
                continue; // Skip rules for other shops
            }

            switch ($rule->rule_type) {
                case 'exclude_label':
                    $dayIndex = array_search($dayName, Rule::RULE_WEEK_DAYS);
                    $ruleData = json_decode($rule->rule_data, true);
                    if (
                        isset($ruleData['label_id'], $ruleData['day']) &&
                        $ruleData['label_id'] == $label->id &&
                        strtolower($ruleData['day']) == $dayIndex
                    ) {
                        return true; // Exclude only for the specific weekday
                    }
                    break;

                case 'exclude_days':
                    $dayIndex = array_search($dayName, Rule::RULE_WEEK_DAYS);
                    if ($dayIndex === $rule->rule_data) {
                        return true;
                    }
                    break;
            }
        }

        return false;
    }


}
