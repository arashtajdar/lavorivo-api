<?php
// app/Repositories/SubscriptionRepository.php
namespace App\Repositories;

use App\Models\Subscription;

class SubscriptionRepository
{
    public function getAllSubscriptions()
    {
        return Subscription::all();
    }

    public function findSubscriptionById($id)
    {
        return Subscription::findOrFail($id);
    }
}
