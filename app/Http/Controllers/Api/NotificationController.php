<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\NotificationResource;
use App\Trait\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('notifiable_id', Auth::id())
            ->where('notifiable_type', get_class(Auth::user()))
            ->latest()
            ->get();
        return ApiResponse::sendResponse(true, 'Notifications retrieved successfully', NotificationResource::collection($notifications));
    }
    public function unread()
    {
        $unread = Notification::where('notifiable_id', Auth::id())
            ->where('notifiable_type', get_class(Auth::user()))
            ->where('is_read', false)
            ->count();
        return ApiResponse::sendResponse(true, 'Notifications retrieved successfully', $unread);
    }


    /**
     * عرض إشعار معين وتحديث حالته إلى مقروء.
     */
    public function show($id)
    {
        $notification = Notification::where('id', $id)
            ->where('notifiable_id', Auth::id())
            ->where('notifiable_type', get_class(Auth::user()))
            ->first();

        if (!$notification) {
            return ApiResponse::errorResponse(false, 'Notification not found');
        }

        // تحديث حالة الإشعار إلى "مقروء"
        $notification->update(['is_read' => true]);
        return ApiResponse::sendResponse(true, 'Notifications retrieved successfully', new NotificationResource($notification));

    }
}