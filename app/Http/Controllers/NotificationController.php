<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $onlyUnread = $request->query('unread', false);
        return response()->json(NotificationService::getUserNotifications($userId, $onlyUnread));
    }

    public function markAsRead($id)
    {
        $notification = NotificationService::markAsRead($id);
        return $notification ? response()->json(['message' => 'Notification marked as read'])
            : response()->json(['error' => 'Notification not found'], 404);
    }
}
