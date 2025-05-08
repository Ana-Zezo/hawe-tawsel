<?php

namespace App\Http\Controllers\Api;

use App\Models\Complaint;
use App\Trait\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\NotificationAdmin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ComplaintRequest;
use App\Notifications\ComplaintReplied;
use App\Http\Resources\ComplaintResource;

class ComplaintController extends Controller
{
    // public function index(string $id)
    // {
    //     $complaints = Complaint::latest()->get();
    //     return ApiResponse::sendResponse(true, 'Data Retrieve Successful', ComplaintResource::collection($complaints));
    // }
    public function index(Request $request)
    {
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,id',
        ]);

        $countryId = $validated['country_id'];
        if (!$countryId) {
            return ApiResponse::errorResponse(false, 'Not Exist Country');
        }
        $complaints = Complaint::where('country_id', $countryId)->latest()->get();
        // dd($complaints);

        return ApiResponse::sendResponse(true, 'Data Retrieve Successful', ComplaintResource::collection($complaints));
    }

    public function store(ComplaintRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();
        $data['country_id'] = Auth::user()->country_id;
        $complaint = Complaint::create($data);

        NotificationAdmin::create([
            'complaint_id' => $complaint->id, 
            'title' => "{$complaint->user->first_name} {$complaint->user->last_name}",
            'description' => "طلب شكوى",
            'is_read' => false, 
        ]);

        return ApiResponse::sendResponse(true, 'Complaint submitted successfully', new ComplaintResource($complaint));
    }
    public function show(Complaint $complaint)
    {
        $complaint->notification()->update(['is_read' => true]);

        return ApiResponse::sendResponse(
            true,
            __('Complaint retrieved successfully'),
            new ComplaintResource($complaint)
        );
    }
    public function reply(Request $request, Complaint $complaint)
    {

        $complaint->load('user'); // تحميل العلاقة
        $request->validate([
            'reply' => 'required|string'
        ]);

        $complaint->update(['reply' => $request->reply]);

        NotificationAdmin::where('complaint_id', $complaint->id)
            ->update(['is_read' => true]);
        if ($complaint->user) {
            $complaint->user->notify(new ComplaintReplied($complaint));
        }
        Notification::create([
            'notifiable_id' => $complaint->user->id,
            'notifiable_type' => get_class($complaint->user),
            'title_en' => 'Hawe Tawsel',
            'title_ar' => 'هاوى توصيل',
            'description_en' => "Your complaint has been replied: {$complaint->reply}",
            'description_ar' => "{$complaint->reply}",
            'is_read' => false
        ]);

        return ApiResponse::sendResponse(true, 'Complaint replied successfully');
    }

    public function destroy(NotificationAdmin $notification)
    {
        $notification->delete();
        return ApiResponse::sendResponse(true, 'Notification deleted successfully');
    }

}