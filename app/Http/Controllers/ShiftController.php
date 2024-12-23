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
        $currentUser = auth()->user();

        // Get all shop IDs owned by the current user
        $shopIds = Shop::where('owner', $currentUser->id)->pluck('id')->toArray();

        // Validate week number
        $weekNumber = $request->get('weekNumber');
        if (!$weekNumber) {
            return response()->json(['Error' => 'weekNumber must be defined'], 400);
        }

        // Get flag to avoid assigning multiple shifts to the same employee per day
        $avoidMultipleShiftsPerDay = $request->get('avoidMultipleShifts', false);

        $start = ($weekNumber - 1) * 7; // Calculate the starting day
        $end = $weekNumber * 7;         // Calculate the ending day

        foreach ($shopIds as $shopId) {
            // Load rules for the shop
            $rules = Rule::where('shop_id', $shopId)->get()->groupBy('employee_id');

            // Fetch all shift labels for the shop
            $shiftLabels = ShiftLabel::where('shop_id', $shopId)->get();

            // Fetch all employees of the shop
            $shop = Shop::findOrFail($shopId);
            $employees = $shop->users;

            if ($employees->isEmpty() || $shiftLabels->isEmpty()) {
                return response()->json(['error' => 'No employees or shift labels found'], 400);
            }

            // Start of the next week
            $startOfNextWeek = Carbon::parse('next monday');

            // Prepare assignment tracking
            $assignmentCounts = [];
            $dailyAssignments = []; // Track daily assignments for avoiding multiple shifts per day

            // Loop through days
            for ($i = $start; $i < $end; $i++) {
                $date = $startOfNextWeek->copy()->addDays($i);
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

                        if ($this->violatesRules($employee, $label, $dayName, $assignmentCounts, $employeeRules)) {
                            continue; // Skip employee if they violate any rule
                        }

                        // Assign the shift to the employee
                        $shiftData[] = [
                            'label' => $label->label,
                            'userId' => $employee->id,
                            'username' => $employee->name,
                            'duration_minutes' => $label->default_duration_minutes ?? 0,
                        ];

                        // Track daily assignments
                        $dailyAssignments[$i][$employee->id] = true;

                        // Track assignment counts
                        $weekStart = $date->startOfWeek()->toDateString();
                        $assignmentCounts[$employee->id][$weekStart] = ($assignmentCounts[$employee->id][$weekStart] ?? 0) + 1;

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

    private function violatesRules($employee, $label, $dayName, $assignmentCounts, $employeeRules)
    {
        foreach ($employeeRules as $rule) {
            if ($rule->shop_id !== $label->shop_id) {
                continue; // Skip rules for other shops
            }

            switch ($rule->rule_type) {
                case 'exclude_label':
                    if ($rule->rule_data['label_id'] == $label->id) {
                        return true;
                    }
                    break;

                case 'exclude_days':
                    if (in_array($dayName, $rule->rule_data['days'])) {
                        return true;
                    }
                    break;

                case 'max_shifts':
                    $weekStart = Carbon::now()->startOfWeek()->toDateString();
                    $currentCount = $assignmentCounts[$employee->id][$weekStart] ?? 0;
                    if ($currentCount >= $rule->rule_data['max_shifts_per_week']) {
                        return true;
                    }
                    break;
            }
        }

        return false;
    }


}
