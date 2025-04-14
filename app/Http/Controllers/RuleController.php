<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rule\DeleteRuleRequest;
use App\Http\Requests\Rule\StoreRuleRequest;
use App\Http\Requests\Rule\UpdateRuleRequest;
use App\Services\RuleService;
use Illuminate\Http\Request;

class RuleController extends Controller
{
    public function __construct(protected RuleService $ruleService) {}

    public function index(Request $request)
    {
        return $this->ruleService->getRules($request);
    }

    public function store(StoreRuleRequest $request)
    {
        return $this->ruleService->createRule($request->validated());
    }

    public function update(UpdateRuleRequest $request, int $id)
    {
        return $this->ruleService->updateRule($id, $request->validated());
    }

    public function destroy(int $id)
    {
        return $this->ruleService->deleteRule($id);
    }

    public function deleteByParams(DeleteRuleRequest $request)
    {
        return $this->ruleService->deleteByParams($request->validated());
    }
}
