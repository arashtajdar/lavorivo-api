<?php
// app/Services/SubscriptionService.php
namespace App\Services;

use App\Models\History;
use App\Models\Notification;
use App\Models\Subscription;
use App\Repositories\SubscriptionRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class SubscriptionService
{
    protected $subscriptionRepo;

    public function __construct(SubscriptionRepository $subscriptionRepo)
    {
        $this->subscriptionRepo = $subscriptionRepo;
    }

    public function getAllSubscriptions()
    {
        return $this->subscriptionRepo->getAllSubscriptions()->map(function ($subscription) {
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
    }

    public function subscribeToPlan($subscriptionId)
    {
        $user = Auth::user();
        $subscription = $this->subscriptionRepo->findSubscriptionById($subscriptionId);

        $user->subscription_id = $subscription->id;
        $user->subscription_expiry_date = now()->addDays($subscription->category === 1 ? 30 : 365);
        $user->save();

        HistoryService::log(History::USER_SUBSCRIBED, [
            "subscription_id" => $subscription->id,
        ]);

        return $user;
    }

    public function validateAndActivatePurchase($transaction)
    {
        // Verify receipt with Apple
        $appleResponse = Http::post('https://buy.itunes.apple.com/verifyReceipt', [
            'receipt-data' => $transaction['receipt'],
            'password' => config('services.apple.shared_secret'),
        ]);

        if ($appleResponse->failed()) {
            throw new \Exception('Invalid receipt');
        }

        $user = auth()->user();

        $user->subscription_id = $transaction['productId'];
        $user->subscription_expiry_date = now()->addMonth();
        $user->save();

        HistoryService::log(History::PURCHASE_SUCCESS, [
            "subscription_id" => $user->subscription_id,
            "subscription_expiry_date" => $user->subscription_expiry_date,
            "userId" => $user->id
        ]);

        $message = "New subscription activated: ";
        NotificationService::create(Auth::id(), Notification::NOTIFICATION_TYPE_NEW_SUBSCRIPTION_ACTIVATED, $message, ["id" => $user->subscription_id]);

        return $user;
    }
}
