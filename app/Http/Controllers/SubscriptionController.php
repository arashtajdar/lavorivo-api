<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::get()->map(function ($subscription) {
            return [
                'id' => $subscription->id,
                'name' => $subscription->name,
                'image' => $subscription->image,
                'realPrice' => $subscription->price,
                'discountedPrice' => $subscription->discounted_price,
                'categoryId' => $subscription->category,
                'categoryName' => Subscription::SUBSCRIPTION_CATEGORIES[$subscription->category] ?? 'Unknown',
                'status' => $subscription->is_active,
            ];
        });

        return response()->json($subscriptions);
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
