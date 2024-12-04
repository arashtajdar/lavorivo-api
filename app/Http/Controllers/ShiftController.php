<?php
namespace App\Http\Controllers;

use App\Models\Shift;
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

        // Organize shifts by date
        $shiftsByDate = $shifts->groupBy('date')
            ->map(function ($shiftsOnDate) {
                return $shiftsOnDate->map(function ($shift) {
                    return [
                        'id' => $shift->id,
                        'shop_id' => $shift->shop_id,
                        'shift_data' => $shift->shift_data,
                    ];
                });
            });

        // Get the start of the current week (Monday)
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);

        // Prepare a full list of dates for the next 4 weeks (28 days)
        $allDates = [];
        for ($i = 0; $i < 28; $i++) {
            $allDates[] = $startOfWeek->copy()->addDays($i)->toDateString();
        }

        // Include all dates, even those without shifts, with empty arrays as default
        $fullShiftsByDate = collect($allDates)->mapWithKeys(function ($date) use ($shiftsByDate) {
            return [
                $date => $shiftsByDate->get($date, []), // Use existing shifts or an empty array
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
