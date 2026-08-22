<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Tour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOperatorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $adminSlugs = Tour::query()
            ->whereNotNull('admin_slug')
            ->distinct()
            ->pluck('admin_slug');

        $query = Admin::query()
            ->with('role')
            ->whereIn('admin_slug', $adminSlugs)
            ->latest();

        if ($request->filled('search')) {
            $term = '%' . trim((string) $request->search) . '%';
            $query->where(function ($inner) use ($term) {
                $inner->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginator = self::paginateQuery($request, $query);

        return self::paginatedApiResponse('Operators retrieved', $paginator, fn (Admin $admin) => $admin->toOperatorArray());
    }

    public function show(Admin $operator): JsonResponse
    {
        $operator->load('role');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Operator retrieved', $operator->toOperatorArray());
    }
}
