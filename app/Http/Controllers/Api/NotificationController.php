<?php
// app/Http/Controllers/Api/NotificationController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
// Add this temporarily to your NotificationController index method
public function index(Request $request)
{
    \Log::info('Auth user id: ' . $request->user()->id);
    \Log::info('Notifications count: ' . $request->user()->notifications()->count());

    return response()->json(
        $request->user()->notifications()->paginate($request->input('per_page', 15))
    );
}

    public function unread(Request $request)
    {
        return response()->json([
            'count'         => $request->user()->unreadNotifications()->count(),
            'notifications' => $request->user()->unreadNotifications()->get(),
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
