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
        $currentUser = auth()->user();

        $userShopsOwned = Shop::where('owner', $currentUser->id)
            ->where('state', 1)
            ->get(['id', 'name']);

        $userShopsManaged = Shop::join('shop_user', 'shops.id', '=', 'shop_user.shop_id')
            ->where('shop_user.user_id', $currentUser->id)
            ->where('shops.state', 1)
            ->get(['shops.id', 'shops.name']);

        $allUserShops = $userShopsOwned->merge($userShopsManaged);

        if ($allUserShops->isEmpty()) {
            return response()->json([]);
        }

        $shopMap = $allUserShops->pluck('name', 'id'); // [shop_id => shop_name]

        $shiftsQuery = Shift::query()->whereIn('shop_id', $shopMap->keys());
        if ($request->has('shop_id')) {
            $shiftsQuery->where('shop_id', $request->shop_id);
        }

        $shifts = $shiftsQuery->get();

        $userMap = User::pluck('name', 'id');

        $dateFrom = $request->has('dateFrom') ? Carbon::parse($request->dateFrom) : Carbon::now()->startOfWeek(Carbon::MONDAY);
        $dateTo = $request->has('dateTo') ? Carbon::parse($request->dateTo) : $dateFrom->copy()->addDays(6);

        if ($dateTo->lt($dateFrom)) {
            return response()->json(['error' => 'dateTo cannot be earlier than dateFrom'], 400);
        }

        $allDates = [];
        for ($date = $dateFrom->copy(); $date->lte($dateTo); $date->addDay()) {
            $allDates[] = $date->toDateString();
        }

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

        $fullShiftsByShop = $shopMap->map(function ($shopName, $shopId) use ($shiftsByShop, $allDates) {
            $datesWithShifts = $shiftsByShop->get($shopId, collect());

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

    public function show($id)
    {
        return Shift::findOrFail($id);
    }

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

        return response()->json($previousShiftInDb);
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

        $dateFrom = Carbon::parse($validatedData['dateFrom']);
        $dateTo = Carbon::parse($validatedData['dateTo']);
        $shopId = $validatedData['shopId'];
        $currentUser = auth()->user();

        if (!UserController::CheckIfUserCanManageThisShop($currentUser->id, $shopId)) {
            Log::error('AUTO: You cannot manage this shop', $validatedData);

            return response()->json(['error' => 'You cannot manage this shop'], 403);
        }

        $rules = Rule::where('shop_id', $shopId)->get()->groupBy('employee_id');

        $shiftLabels = ShiftLabel::where('shop_id', $shopId)->get();

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

        $offDays = UserOffDay::whereIn('user_id', $employees->pluck('id'))
            ->whereBetween('off_date', [$dateFrom, $dateTo])
            ->where('status', UserOffDay::USER_OFF_DAY_STATUS_APPROVED)
            ->get()
            ->groupBy('user_id');

        $dailyAssignments = [];

        $i = 0;
        for ($date = $dateFrom; $date->lte($dateTo); $date->addDay()) {
            $i++;
            $dayName = $date->format('l');
            $dateString = $date->toDateString();
            $shiftData = [];

            foreach ($shiftLabels as $label) {
                $shuffledEmployees = $employees->shuffle();

                foreach ($shuffledEmployees as $employee) {
                    $maxShifts = $employee->max_shifts_per_week;

                    $weekStart = Carbon::parse($dateString)->startOfWeek();
                    $weekEnd = Carbon::parse($dateString)->endOfWeek();
                    $weeklyShiftCount = Shift::where('shop_id', $shopId)
                        ->whereBetween('date', [$weekStart, $weekEnd])
                        ->get()
                        ->sum(function ($shift) use ($employee) {
                            $shiftData = $shift->shift_data;
                            return count(array_filter($shiftData, fn($s) => $s['userId'] == $employee->id));
                        });

                    if ($weeklyShiftCount >= $maxShifts) {
                        continue;
                    }
                    if (isset($offDays[$employee->id]) && $offDays[$employee->id]->contains('off_date', $dateString)) {
                        continue;
                    }
                    if (isset($dailyAssignments[$dateString][$employee->id])) {
                        continue;
                    }
                    $employeeRules = $rules->get($employee->id, []);
                    if ($this->violatesRules($label, $dayName, $employeeRules)) {
                        continue;
                    }

                    $shiftData[] = [
                        'label' => [
                            "id" => $label->id,
                            "name" => $label->label,
                        ],
                        'userId' => $employee->id,
                        'username' => $employee->name,
                        'duration_minutes' => $label->default_duration_minutes ?? 0,
                    ];

                    $dailyAssignments[$dateString][$employee->id] = true;

                    break;
                }
            }

            $existingShift = Shift::query()->where('shop_id', $shopId)->where('date', $dateString)->first();

            if ($existingShift) {
                $existingShift->shift_data = $shiftData;
                $existingShift->save();
            } else {
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
                continue;
            }

            switch ($rule->rule_type) {
                case 'exclude_label':
                    $dayIndex = array_search($dayName, Rule::RULE_WEEK_DAYS);
                    $ruleData = $rule->rule_data;
                    if (
                        isset($ruleData['label_id'], $ruleData['day']) &&
                        $ruleData['label_id'] == $label->id &&
                        $ruleData['day'] == $dayIndex
                    ) {
                        return true;
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
