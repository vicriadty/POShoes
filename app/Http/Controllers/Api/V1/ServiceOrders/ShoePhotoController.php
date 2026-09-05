<?php

namespace App\Http\Controllers\Api\V1\ServiceOrders;

use App\Domain\ShoePhotos\Actions\UploadShoePhoto;
use App\Http\Controllers\Controller;
use App\Http\Requests\ShoePhotos\StoreShoePhotoRequest;
use App\Http\Resources\ShoePhotoResource;
use App\Models\ServiceOrder;
use App\Models\ShoeItem;
use App\Models\ShoePhoto;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ShoePhotoController extends Controller
{
    public function index(Request $request, ServiceOrder $order): JsonResponse
    {
        $photos = $order->photos()
            ->with('shoeItem')
            ->orderByDesc('id')
            ->get();

        return ApiResponse::ok(ShoePhotoResource::collection($photos));
    }

    public function store(StoreShoePhotoRequest $request, ServiceOrder $order, ShoeItem $shoe): JsonResponse
    {
        if ((int) $shoe->service_order_id !== (int) $order->id) {
            abort(404);
        }

        $photo = UploadShoePhoto::upload(
            $shoe,
            $request->file('photo'),
            (string) $request->input('type'),
            $request->user()->id,
        );

        $photo->load('shoeItem');

        return ApiResponse::created(new ShoePhotoResource($photo));
    }

    public function file(Request $request, ShoePhoto $photo): Response
    {
        if (! Storage::disk('photos')->exists($photo->file_path)) {
            abort(404);
        }

        $content = Storage::disk('photos')->get($photo->file_path);
        $mime = $photo->mime_type ?? Storage::disk('photos')->mimeType($photo->file_path);

        return response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
