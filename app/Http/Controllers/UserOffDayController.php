<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\User;
use App\Models\UserOffDay;
use App\Services\HistoryService;
use Illuminate\Http\Request;

class UserOffDayController extends Controller
{
// Show a list of all off days
    public function index()
    {
        $offDays = UserOffDay::with('user')->get();  // Including the user relationship
        return response()->json($offDays);
    }

    public function listOffDaysToManage()
    {
        $currentUserId = auth()->id();
        $userIds = User::where('employer', $currentUserId)->pluck('id')->toArray();
        $userIds[] = $currentUserId;
        $offDays = UserOffDay::whereIn('user_id', $userIds)->with('user')->get();  // Including the user relationship
        return response()->json($offDays);

    }

    public function UpdateOffDayStatus(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:user_off_days,id',
            'status' => 'required'
        ]);
        $userOffDay = UserOffDay::findOrFail($validated['id']);
        $userOffDay->update($validated);

        if($validated['status'] == 1){
            HistoryService::log(History::APPROVE_OFF_DAY, $validated);
        }else{
            HistoryService::log(History::REJECT_OFF_DAY, $validated);
        }

        return response()->json($userOffDay);

    }

// Store a new off day
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'off_date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $offDay = UserOffDay::create($validated);

        return response()->json($offDay, 201);
    }

// Show a specific off day
    public function show($id)
    {
        $offDay = UserOffDay::with('user')->findOrFail($id);
        return response()->json($offDay);
    }

// Update an off day
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'off_date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $offDay = UserOffDay::findOrFail($id);
        $offDay->update($validated);
        return response()->json($offDay);
    }

// Delete an off day
    public function destroy($id)
    {
        $offDay = UserOffDay::findOrFail($id);
        $offDay->delete();
        return response()->json(null, 204);
    }
}
