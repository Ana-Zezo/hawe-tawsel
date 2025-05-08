<?php

namespace App\Http\Controllers\Api;

use App\Models\Country;
use App\Trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Withdraw;
use App\Models\Complain;
use App\Http\Requests\CountryRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Country\CountryResource;

class CountryController extends Controller
{


    public function index(Request $request): JsonResponse
{
    $data = $request->input('data'); 

    if ($data === 'withdraws') {
        $countries = Country::withCount([
            'withdraws as withdraw_count' => function ($query) {
                $query->where('status', 'pending');
            },
        ])->get();
    } elseif ($data === 'complaints') {
        $countries = Country::withCount([
            'complaints as complain_count' => function ($query) {
                $query->whereNull('reply');
            },
        ])->get();
    } else {
        $countries = Country::all();
    }

    return ApiResponse::sendResponse(
        true,
        'Data Retrieve Successful',
        CountryResource::collection($countries)
    );
}

    public function store(CountryRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $data['image'] = 'storage/' . $request->file('image')->store('uploads/images/country', 'public');
        }

        $country = Country::create($data); // Create country with the image path

        return ApiResponse::sendResponse(true, 'Country created successfully', new CountryResource($country));
    }

    public function show(Country $country): JsonResponse
    {
        return ApiResponse::sendResponse(true, new CountryResource($country));
    }

    public function update(Request $request, Country $country): JsonResponse
    {
        $validateData = $request->validate([
            'name_en' => 'sometimes|string',
            'name_ar' => 'sometimes|string',
            'country_code' => 'sometimes|string',
            'currency' => 'sometimes|string',
            'kilo' => 'sometimes|numeric',
            'tax_amount' => 'sometimes|numeric',
            'cover_price' => 'sometimes|numeric',
        ]);

      
    if ($request->hasFile('image') && $request->file('image')->isValid()) {
        $file = $request->file('image');
        $path = 'uploads/images/country';

        // حذف الصورة القديمة إذا كانت موجودة
        if (!empty($country->image)) {
            $oldImagePath = str_replace('storage/', '', $country->image);
            if (Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }
        }

        // حفظ الصورة الجديدة
        $uploadedFilePath = $file->store($path, 'public');
        $validateData['image'] = 'storage/' . $uploadedFilePath;
    }

        $country->update($validateData);

        return ApiResponse::sendResponse(true, 'Country updated Successfully', new CountryResource($country));
    }


    public function destroy(Country $country): JsonResponse
    {
        $country->delete();

        return ApiResponse::sendResponse(true, 'Country Deleted Successfully');
    }
}