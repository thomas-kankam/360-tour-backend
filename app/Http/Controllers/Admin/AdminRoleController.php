<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $paginator = self::paginateQuery($request, Role::with('permissions')->latest());

        return self::paginatedApiResponse('Roles retrieved', $paginator, fn (Role $role) => $role->toApiArray());
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('permissions');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Role retrieved', $role->toApiArray());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $data['name'],
        ]);

        $role->permissions()->sync($data['permissions']);

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Role created', $role->fresh('permissions')->toApiArray());
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'sometimes|array|min:1',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        if (isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        if (isset($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Role updated', $role->fresh('permissions')->toApiArray());
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->admins()->exists()) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, 'Role is assigned to one or more admins', []);
        }

        $role->permissions()->detach();
        $role->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Role deleted', []);
    }
}
