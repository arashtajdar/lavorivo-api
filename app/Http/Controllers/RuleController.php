<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\Rule;
use App\Services\HistoryService;
use Illuminate\Http\Request;

class RuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Rule::query();

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        return response()->json($query->get());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'shop_id' => 'required|exists:shops,id', // Add validation for shop_id
            'rule_type' => 'required|string',
            'rule_data' => 'required',
        ]);

        $rule = Rule::create($validated);
        HistoryService::log(History::RULE_ADDED, $validated);

        return response()->json($rule, 201);
    }
    public function deleteByParams(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'shop_id' => 'required|exists:shops,id', // Validation for shop_id
            'rule_type' => 'required|string',
            'rule_data' => 'required',
        ]);

        $rule = Rule::where('employee_id', $validated['employee_id'])
            ->where('shop_id', $validated['shop_id'])
            ->where('rule_type', $validated['rule_type'])
            ->where('rule_data', $validated['rule_data']) // Ensure rule_data matches
            ->first();

        if (!$rule) {
            return response()->json(['error' => 'Rule not found'], 404);
        }

        $rule->delete(); // Delete the rule
        HistoryService::log(History::RULE_REMOVED, $validated);

        return response()->json(['message' => 'Rule deleted successfully'], 200);
    }



    /**
     * Display the specified resource.
     */
    public function show(Rule $rule)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Rule $rule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $rule = Rule::findOrFail($id);

        $validated = $request->validate([
            'rule_type' => 'required|string',
            'rule_data' => 'required|array',
        ]);

        $rule->update($validated);
        HistoryService::log(History::UPDATE_RULE, $validated);

        return response()->json($rule);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $rule = Rule::findOrFail($id);
        $rule->delete();

        return response()->json(['message' => 'Rule deleted successfully']);
    }


}
