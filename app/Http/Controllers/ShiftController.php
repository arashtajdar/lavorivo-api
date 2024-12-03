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

        $shifts = $shiftsQuery->with(['assignments.user'])->get();

        // Format the response to include assignments per shift index
        $formattedShifts = $shifts->map(function ($shift) {
            // Prepare assignments for each shift index
            $assignments = [];
            foreach ($shift->shift_data as $index => $data) {
                $schedule = $shift->assignments->firstWhere('shift_index', $index);
                $assignments[] = [
                    'shift_index' => $index,
                    'employee' => $schedule ? $schedule->user : null,
                ];
            }

            return [
                'id' => $shift->id,
                'shop_id' => $shift->shop_id,
                'date' => $shift->date,
                'shift_data' => $shift->shift_data,
                'assignments' => $assignments,
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
            'date' => 'sometimes|date',
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
}
