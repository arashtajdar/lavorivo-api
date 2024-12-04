<?php
namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

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
    public function employeeShifts(Request $request)
    {
        $user = $request->user(); // Get the currently authenticated user

        $shifts = \App\Models\Schedule::with(['shift'])
            ->where('user_id', $user->id) // Filter by the user's ID
            ->get()
            ->map(function ($schedule) {
                $shift = $schedule->shift;

                return [
                    'date' => $shift->date,
                    'label' => $shift->shift_data[$schedule->shift_index]['label'] ?? 'Unknown',
                    'duration_minutes' => $shift->shift_data[$schedule->shift_index]['duration_minutes'] ?? 0,
                ];
            });

        return response()->json($shifts);
    }

}
