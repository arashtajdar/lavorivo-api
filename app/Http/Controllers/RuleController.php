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
//{ "label_id": 18, "day": "Monday" }
        $rule = Rule::create($validated);
        HistoryService::log(History::RULE_ADDED, $validated);

        return response()->json($rule, 201);
    }
    public function deleteByParams(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'shop_id' => 'required|exists:shops,id',
            'rule_type' => 'required|string',
            'rule_data' => 'required', // Expected format: {"label_id":18, "day":"Monday"}
        ]);

        $rules = Rule::where('employee_id', $validated['employee_id'])
            ->where('shop_id', $validated['shop_id'])
            ->where('rule_type', $validated['rule_type'])
            ->get(); // Get all matching rules instead of first()

        if ($rules->isEmpty()) {
            return response()->json(['error' => 'Rule not found'], 404);
        }

        // Convert `rule_data` to an array for comparison
        $incomingRuleData = is_array($validated['rule_data']) ? $validated['rule_data'] : json_decode($validated['rule_data'], true);

        // Filter out only the rules with the same day
        $rulesToDelete = $rules->filter(function ($rule) use ($incomingRuleData) {
            $existingRuleData = $rule->rule_data;
            return isset($existingRuleData['day']) && $existingRuleData['day'] == $incomingRuleData['day'];
        });

        if ($rulesToDelete->isEmpty()) {
            return response()->json(['error' => 'No matching rule found for the specified day'], 404);
        }

        // Delete only the matching rules
        Rule::whereIn('id', $rulesToDelete->pluck('id'))->delete();

        HistoryService::log(History::RULE_REMOVED, $validated);

        return response()->json(['message' => 'Rule(s) deleted successfully'], 200);
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
