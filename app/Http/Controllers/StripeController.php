<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Subscription;
use App\Models\User;
use Stripe\Webhook;
use Stripe\Checkout\Session as StripeSession;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id'
        ]);

        $user = auth()->user();
        $subscription = Subscription::findOrFail($request->subscription_id);

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'customer_email' => $user->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $subscription->name,
                    ],
                    'unit_amount' => $subscription->discounted_price * 100, // Convert to cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => config('app.frontend_url') . '/subscription/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.frontend_url') . '/subscription/cancel',
            'metadata' => [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
            ],
        ]);

        return response()->json($session);
    }


public function handleWebhook(Request $request)
{
    try {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data['object'];

            $user = User::find($session['metadata']['user_id']);
            $subscriptionId = $session['metadata']['subscription_id'];

            if ($user) {
                $user->subscription_id = $subscriptionId;
                $user->subscription_expiry = now()->addMonth(); // Adjust based on plan
                $user->save();
            }
        }
    } catch (\Exception $e) {
        Log::error('Stripe Webhook Error: ' . $e->getMessage());
    }

    return response()->json(['status' => 'success']);
}

}
