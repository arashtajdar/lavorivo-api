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
        Stripe::setApiKey(config('services.stripe.secret'));

        $subscription = Subscription::where('id', $request->subscription_id)->firstOrFail();
        $user = auth()->user();

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $subscription->product_id
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
                'product_id' => $subscription->product_id
            ],
        ]);

        return response()->json(['url' => $session->url]);
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
                $subscription = Subscription::where('product_id', $session['metadata']['product_id'])->first();

                if ($user && $subscription) {
                    $user->subscription_id = $subscription->id;
                    $user->subscription_expiry = now()->addMonth(); // Adjust based on plan duration
                    $user->save();
                }
            }
        } catch (\Exception $e) {
            Log::error('Stripe Webhook Error: ' . $e->getMessage());
        }

        return response()->json(['status' => 'success']);
    }

}
