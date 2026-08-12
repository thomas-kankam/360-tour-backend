<?php
namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ApiTransformer
{
    protected const API_SUCCESS = 200;

    protected const API_FAIL = 401;

    protected const API_FOUND = 404;

    protected const API_NOT_FOUND = 403;

    protected const API_CREATED = 201;

    protected const API_BAD_REQUEST = 400;

    protected static function apiResponse(bool $in_error, string $message, int|string $status_code, string $reason, ?array $data = []): JsonResponse
    {
        $code = 200;

        switch ((string) $status_code) {
            case '000':
            case '200':
            case '005':
            case '002':
            case '003':
                $code = 200;
                break;
            case '001':
                $code = 201;
                break;
            case '997':
                $code = 200;
                break;
            case '999':
                $code = 500;
                break;
            case '004':
                $code = 200;
                break;
            default:
                break;
        }

        return response()->json([
            "data" => [
                "status_code"   => $status_code,
                "message"       => $message,
                "in_error"      => $in_error,
                "reason"        => $reason,
                "data"          => $data,
                "point_in_time" => now(),
            ],
        ], $code);
    }

    protected static function paginateQuery(Request $request, Builder $query, int $defaultPerPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min((int) $request->input('per_page', $defaultPerPage), 100));
        $page = max(1, (int) $request->input('page', 1));

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    protected static function paginatedApiResponse(
        string $reason,
        LengthAwarePaginator $paginator,
        callable $transform,
        int|string $statusCode = self::API_SUCCESS,
    ): JsonResponse {
        return self::apiResponse(false, 'Action Successful', (string) $statusCode, $reason, [
            'items' => collect($paginator->items())->map($transform)->values()->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
