<?php

namespace App\Http\Controllers\Api\V1\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Services\StoreServiceCatalogRequest;
use App\Http\Requests\Services\StoreServiceCategoryRequest;
use App\Http\Requests\Services\UpdateServiceCatalogRequest;
use App\Http\Resources\ServiceCatalogResource;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\ServiceCatalog;
use App\Models\ServiceCategory;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceCatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = ServiceCatalog::query()
            ->with('category')
            ->when(
                $request->boolean('active_only'),
                fn ($q) => $q->where('active', true),
            )
            ->when(
                $request->filled('category_id'),
                fn ($q) => $q->where('category_id', (int) $request->input('category_id')),
            )
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($q) use ($request) {
                    $search = (string) $request->input('search');
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                }),
            )
            ->orderBy('code')
            ->paginate($request->integer('per_page', 15));

        return ApiResponse::paginated(
            $services,
            fn ($paginator) => ServiceCatalogResource::collection($paginator->items())->resolve(),
        );
    }

    public function store(StoreServiceCatalogRequest $request): JsonResponse
    {
        $service = ServiceCatalog::create($request->safe()->only([
            'code', 'category_id', 'name', 'description',
            'base_price', 'estimated_duration_minutes',
            'requires_before_after_photo', 'active',
        ]));

        return ApiResponse::created(new ServiceCatalogResource($service->load('category')));
    }

    public function show(Request $request, ServiceCatalog $service): JsonResponse
    {
        return ApiResponse::ok(new ServiceCatalogResource($service->load('category')));
    }

    public function update(UpdateServiceCatalogRequest $request, ServiceCatalog $service): JsonResponse
    {
        $service->update($request->safe()->only([
            'code', 'category_id', 'name', 'description',
            'base_price', 'estimated_duration_minutes',
            'requires_before_after_photo', 'active',
        ]));

        return ApiResponse::ok(new ServiceCatalogResource($service->refresh()->load('category')));
    }

    public function categories(Request $request): JsonResponse
    {
        $categories = ServiceCategory::query()
            ->when(
                $request->boolean('active_only'),
                fn ($q) => $q->where('active', true),
            )
            ->orderBy('name')
            ->get();

        return ApiResponse::ok(ServiceCategoryResource::collection($categories));
    }

    public function storeCategory(StoreServiceCategoryRequest $request): JsonResponse
    {
        $category = ServiceCategory::create($request->safe()->only(['name', 'code', 'active']));

        return ApiResponse::created(new ServiceCategoryResource($category));
    }
}
