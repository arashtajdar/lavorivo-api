<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\Subscription;
use App\Services\HistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        HistoryService::log(History::USER_SUBSCRIBED, [
            "subscription_id" => $subscription->id,
        ]);
        return response()->json(['message' => 'Subscription updated successfully.', 'user' => $user]);
    }

    public function validatePurchase(Request $request)
    {
        $transaction = $request->input('transaction');

        // Verify receipt with Apple
        $appleResponse = Http::post('https://buy.itunes.apple.com/verifyReceipt', [
            'receipt-data' => $transaction['receipt'],
            'password' => config('services.apple.shared_secret'), // Set this in .env
        ]);

        if ($appleResponse->failed()) {
            return response()->json(['error' => 'Invalid receipt'], 400);
        }

        // Get user & update subscription
        $user = auth()->user();
        $user->subscription_id = $transaction['productId'];
        $user->subscription_expiry_date = now()->addMonth(); // Adjust based on plan
        $user->save();
        HistoryService::log(History::PURCHASE_SUCCESS, [
            "subscription_id" => $user->subscription_id,
            "subscription_expiry_date" => $user->subscription_expiry_date,
            "userId" => $user->id
        ]);
        return response()->json(['message' => 'Subscription updated', 'user' => $user]);
    }
}
