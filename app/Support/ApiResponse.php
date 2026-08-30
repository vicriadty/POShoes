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
     *
     * @param  (callable(LengthAwarePaginator): array<int, mixed>)|null  $map
     */
    public static function paginated(LengthAwarePaginator $paginator, ?callable $map = null): JsonResponse
    {
        $items = $map ? $map($paginator) : $paginator->items();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
