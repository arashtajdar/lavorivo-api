<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        return response()->json(Subscription::all());
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id'
        ]);

        $user = auth()->user();
        $subscription = Subscription::findOrFail($request->subscription_id);

        $user->subscription_id = $subscription->id;
        $user->subscription_expiry_date = now()->addDays($subscription->category === 1 ? 30 : 365);
        $user->save();

        return response()->json(['message' => 'Subscription updated successfully.', 'user' => $user]);
    }
}
