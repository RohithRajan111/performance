<?php
// app/Http/Controllers/NotificationController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead($id)
    {
        auth()->user()->markNotificationAsRead($id);
        
        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        auth()->user()->markAllNotificationsAsRead();
        
        return response()->json(['success' => true]);
    }

    public function getUnreadCount()
    {
        return response()->json([
            'count' => auth()->user()->unreadNotifications()->count()
        ]);
    }

    public function getRecent()
    {
        $notifications = auth()->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json(['data' => $notifications]);
    }
}
