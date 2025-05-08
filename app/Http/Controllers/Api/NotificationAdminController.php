<?php

namespace App\Http\Controllers\Api;

use App\Trait\ApiResponse;
use Illuminate\Http\Request;
use App\Models\NotificationAdmin;
use App\Http\Controllers\Controller;

class NotificationAdminController extends Controller
{
    public function index()
    {
        $count = NotificationAdmin::where('is_read', false)->count();

        $notifications = NotificationAdmin::with(['withdraw.driver:id,image', 'complaint.user:id,image'])
            ->latest()
            ->get()
            ->map(function ($notification) {
                $data = [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'description' => $notification->description,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at->format('d-m-Y'),
                    'lastTime' => $notification->created_at->diffForHumans()
                ];

                if ($notification->withdraw_id !== null && $notification->withdraw && $notification->withdraw->driver) {
                    $data['type'] = 'withdraw';
                    $data['image'] = $notification->withdraw->driver->image;
                } elseif ($notification->complaint_id !== null && $notification->complaint && $notification->complaint->user) {
                    $data['type'] = 'complaint';
                    $data['image'] = $notification->complaint->user->image;
                } else {
                    $data['image'] = null;
                }

                return $data;
            });

        return ApiResponse::sendResponse(
            true,
            'Notifications retrieved successfully',
            [
                'count' => $count,
                'notifications' => $notifications
            ]
        );
    }


    /**
     * عرض إشعار معين وتحديث حالته إلى مقروء
     */
    public function show(NotificationAdmin $notification)
    {
        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->update(['is_read' => true]); // تحديث حالة الإشعار إلى مقروء

        return ApiResponse::sendResponse(true, 'Notification retrieved successfully', $notification);
    }

    /**
     * حذف إشعار معين
     */
    public function destroy(NotificationAdmin $notification)
    {
        $notification->delete();
        return ApiResponse::sendResponse(true, 'Notification deleted successfully');
    }
}