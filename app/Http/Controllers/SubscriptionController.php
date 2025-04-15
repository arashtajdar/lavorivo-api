<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subscription\SubscribeRequest;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function index()
    {
        $subscriptions = $this->subscriptionService->getAllSubscriptions();
        return response()->json($subscriptions);
    }

    public function subscribe(SubscribeRequest $request)
    {
        $user = $this->subscriptionService->subscribeToPlan($request->subscription_id);
        return response()->json(['message' => 'Subscription updated successfully.', 'user' => $user]);
    }

    public function validatePurchase(Request $request)
    {
        $transaction = $request->input('transaction');

        try {
            $user = $this->subscriptionService->validateAndActivatePurchase($transaction);
            return response()->json(['message' => 'Subscription updated', 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
