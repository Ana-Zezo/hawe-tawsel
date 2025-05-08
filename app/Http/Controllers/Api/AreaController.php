<?php

namespace App\Http\Controllers\Api;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\AreaRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAreaRequest;
use App\Http\Resources\Area\AreaResource;
use App\Trait\ApiResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AreaController extends Controller
{


    // public function index(): JsonResponse
    // {
    //     // $areas = Area::useFilters()->get();
    //     $locale = request()->header('local', 'ar');
    //     $nameField = $locale === 'ar' ? 'name_ar' : 'name_en';

    //     $areas = Area::useFilters()->where('status', 'active')
    //         ->select([
    //             DB::raw("$nameField as name"),
    //             'id',
    //             'radius',
    //             'longitude',
    //             'latitude',
    //             'coordinates',
    //             'status',
    //         ])
    //         ->get();

    //     return ApiResponse::sendResponse(true, 'Areas retrieved successfully', AreaResource::collection($areas));
    //     if (empty($areas)) {
    //         return ApiResponse::sendResponse(true, 'No regions found');
    //     }

    // }
   public function index(): JsonResponse
    {
        $areas = Area::useFilters()->where('status', 'active')->get();

        if ($areas->isEmpty()) {
            return ApiResponse::sendResponse(false, __('messages.no_regions_found'), []);
        }

        return ApiResponse::sendResponse(true, __('messages.areas_retrieved_successful'), AreaResource::collection($areas));
    }



   public function store(AreaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['coordinates'] = json_encode($request['coordinates']);
        $area = Area::create($request->validated());
        return ApiResponse::sendResponse(true, __('messages.area_created_successful'), new AreaResource($area));
    }




    public function update(UpdateAreaRequest $request, Area $area): JsonResponse
    {
        $data = $request->validated();
        $data['coordinates'] = isset($data['coordinates']) ? json_encode($data['coordinates']) : $area->coordinates;
        $area->update($data);
        $locale = request()->header('Accept-Language', 'ar');
        $nameField = $locale === 'ar' ? 'name_ar' : 'name_en';

        $updatedArea = Area::select([
            'id',
            DB::raw("$nameField as name"),
            'radius',
            'longitude',
            'latitude',
            'coordinates',
            'status',
        ])->find($area->id);

      return ApiResponse::sendResponse(true, __('messages.area_updated_successful'), new AreaResource($updatedArea));
    }


    public function destroy(Area $area): JsonResponse
    {
        $area->delete();

         return ApiResponse::sendResponse(true, __('messages.area_deleted_successful'));
    }


}