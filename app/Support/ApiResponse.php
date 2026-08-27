<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Helper response API yang konsisten (lihat docs/design/api-convention.md).
 */
final class ApiResponse
{
    /**
     * Response sukses dengan data tunggal.
     */
    public static function ok(mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }

    /**
     * Response sukses tanpa bodi.
     */
    public static function noContent(int $status = 204): JsonResponse
    {
        return response()->json(null, $status);
    }

    /**
     * Response resource dibuat (201).
     */
    public static function created(mixed $data = null): JsonResponse
    {
        return self::ok($data, 201);
    }

    /**
     * Response koleksi yang sudah dipaginate.
     */
    public static function paginated(LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
