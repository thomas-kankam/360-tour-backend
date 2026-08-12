<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAdminWelcomeEmailJob;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $paginator = self::paginateQuery($request, Admin::with('role.permissions')->latest());

        return self::paginatedApiResponse('Admins retrieved', $paginator, fn (Admin $admin) => $admin->toAuthArray());
    }

    public function show(Admin $admin): JsonResponse
    {
        $admin->load('role.permissions');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Admin retrieved', $admin->toAuthArray());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string',
            'email' => 'required|email|unique:admins,email',
            'role_id' => 'required|integer|exists:roles,id',
            'last_name' => 'nullable|string',
            'phone_number' => 'nullable|string',
        ]);

        $admin = Admin::create([
            'admin_slug' => (string) Str::uuid(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'email' => $data['email'],
            'role_id' => $data['role_id'],
            'status' => 'active',
        ]);

        SendAdminWelcomeEmailJob::dispatch($admin);

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Admin created', $admin->fresh(['role.permissions'])->toAuthArray());
    }

    public function update(Request $request, Admin $admin): JsonResponse
    {
        $data = $request->validate([
            'first_name' => 'sometimes|string',
            'last_name' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'email' => 'sometimes|email|unique:admins,email,'.$admin->id,
            'role_id' => 'sometimes|integer|exists:roles,id',
            'status' => 'sometimes|string',
        ]);

        $admin->update($data);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Admin updated', $admin->fresh(['role.permissions'])->toAuthArray());
    }

    public function destroy(Admin $admin): JsonResponse
    {
        $admin->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Admin deleted', []);
    }
}
