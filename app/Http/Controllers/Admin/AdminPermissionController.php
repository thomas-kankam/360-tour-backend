<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminPermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $paginator = self::paginateQuery($request, Permission::query()->latest());

        return self::paginatedApiResponse('Permissions retrieved', $paginator, fn (Permission $permission) => $permission->toApiArray());
    }

    public function show(Permission $permission): JsonResponse
    {
        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Permission retrieved', $permission->toApiArray());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
        ]);

        $name = self::permissionNameFromLabel($data['label']);

        validator(['name' => $name], [
            'name' => 'required|string|max:255|unique:permissions,name',
        ])->validate();

        $permission = Permission::create([
            'name' => $name,
            'label' => $data['label'],
        ]);

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Permission created', $permission->toApiArray());
    }

    public function update(Request $request, Permission $permission): JsonResponse
    {
        $data = $request->validate([
            'label' => 'sometimes|required|string|max:255',
        ]);

        if (isset($data['label'])) {
            $name = self::permissionNameFromLabel($data['label']);

            validator(['name' => $name], [
                'name' => 'required|string|max:255|unique:permissions,name,'.$permission->id,
            ])->validate();

            $data['name'] = $name;
        }

        $permission->update($data);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Permission updated', $permission->fresh()->toApiArray());
    }

    private static function permissionNameFromLabel(string $label): string
    {
        return Str::slug($label, '_');
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $permission->roles()->detach();
        $permission->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Permission deleted', []);
    }
}
