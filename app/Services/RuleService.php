<?php

namespace App\Services;

use App\Models\History;
use App\Models\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RuleService
{
    public function getRules(Request $request): JsonResponse
    {
        $query = Rule::query();

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        return response()->json($query->get());
    }

    public function createRule(array $data): JsonResponse
    {
        $rule = Rule::create($data);
        HistoryService::log(History::RULE_ADDED, $data);

        return response()->json($rule, 201);
    }

    public function updateRule(int $id, array $data): JsonResponse
    {
        $rule = Rule::findOrFail($id);
        $rule->update($data);

        HistoryService::log(History::UPDATE_RULE, $data);

        return response()->json($rule);
    }

    public function deleteRule(int $id): JsonResponse
    {
        $rule = Rule::findOrFail($id);
        $rule->delete();

        return response()->json(['message' => 'Rule deleted successfully']);
    }

    public function deleteByParams(array $data): JsonResponse
    {
        $rules = Rule::where('employee_id', $data['employee_id'])
            ->where('shop_id', $data['shop_id'])
            ->where('rule_type', $data['rule_type'])
            ->get();

        if ($rules->isEmpty()) {
            return response()->json(['error' => 'Rule not found'], 404);
        }

        $incoming = is_array($data['rule_data']) ? $data['rule_data'] : json_decode($data['rule_data'], true);

        $toDelete = $rules->filter(function ($rule) use ($incoming) {
            return isset($rule->rule_data['day'], $rule->rule_data['label_id']) &&
                $rule->rule_data['day'] == $incoming['day'] &&
                $rule->rule_data['label_id'] == $incoming['label_id'];
        });

        if ($toDelete->isEmpty()) {
            return response()->json(['error' => 'No matching rule found for the specified day'], 404);
        }

        Rule::whereIn('id', $toDelete->pluck('id'))->delete();
        HistoryService::log(History::RULE_REMOVED, $data);

        return response()->json(['message' => 'Rule(s) deleted successfully']);
    }
}
