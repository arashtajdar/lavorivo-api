<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public static function create($userId, $type, $message, $data = [])
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'is_read' => false
        ]);
    }

    public static function markAsRead($id)
    {
        $notification = Notification::find($id);
        if ($notification) {
            $notification->update(['is_read' => true]);
        }
        return $notification;
    }

    public static function getUserNotifications($userId, $onlyUnread = false)
    {
        $query = Notification::where('user_id', $userId)->latest();
        if ($onlyUnread) {
            $query->where('is_read', false);
        }
        return $query->get();
    }
}
