<?php

namespace App\Http\Controllers\Api;

use App\Models\Banner;
use App\Trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\BannerRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

use App\Http\Resources\Banner\BannerResource;

class BannerController extends Controller
{


    public function index(): JsonResponse
    {
        $banners = Banner::get();
        return ApiResponse::sendResponse(true, 'Banner retrieve successful', BannerResource::collection($banners));
    }

    public function store(BannerRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('uploads/images/banners', 'public'); // حفظ الصورة في storage/app/public/banners

            $data['image'] = 'storage/' . $path; // حفظ المسار ليكون قابل للوصول عبر public/storage
        }

        $banner = Banner::create($data);

        return ApiResponse::sendResponse(true, 'Banner created successfully', new BannerResource($banner));
    }


    public function update(Request $request, Banner $banner): JsonResponse
    {
        $validatedData = $request->validate([
            'image' => 'sometimes|image|mimes:jpeg,png,jpg',
        ]);

        if ($request->hasFile('image')) {
            // حذف الصورة القديمة إذا كانت موجودة
            if (!empty($banner->image)) {
                $oldImagePath = str_replace('storage/', '', $banner->image);
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            // حفظ الصورة الجديدة في storage/app/public/banners
            $file = $request->file('image');
            $path = $file->store('banners', 'public');
            $validatedData['image'] = 'storage/' . $path;
        }

        $banner->update($validatedData);

        return ApiResponse::sendResponse(true, 'Banner updated successfully', new BannerResource($banner));
    }

    public function destroy(Banner $banner): JsonResponse
    {
        if (!empty($banner->image)) {
            $oldImagePath = str_replace('storage/', '', $banner->image);
            if (Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }
        }

        $banner->delete();

        return ApiResponse::sendResponse(true, 'Banner deleted successfully');
    }


}