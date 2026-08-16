<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Support\AccessGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('roles.index', ['roles' => Role::query()->withCount(['users', 'permissions'])->orderBy('title')->paginate(15)]);
    }

    public function create(): View
    {
        return view('roles.create', ['permissionGroups' => Permission::query()->orderBy('group')->orderBy('title')->get()->groupBy('group')]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::query()->create($request->safe()->except('permission_ids') + ['guard_name' => 'web']);
        $role->syncPermissions($request->validated('permission_ids', []));

        return redirect()->route('roles.index')->with('success', 'نقش با موفقیت ایجاد شد.');
    }

    public function edit(Request $request, Role $role, AccessGuard $guard): View
    {
        $guard->ensureRoleCanChange($request->user(), $role);
        $role->load('permissions');

        return view('roles.edit', ['role' => $role, 'permissionGroups' => Permission::query()->orderBy('group')->orderBy('title')->get()->groupBy('group')]);
    }

    public function update(UpdateRoleRequest $request, Role $role, AccessGuard $guard): RedirectResponse
    {
        $guard->ensureRoleCanChange($request->user(), $role);
        $data = $request->safe()->except('permission_ids');
        if ($role->is_system) {
            unset($data['name']);
        }
        $role->update($data);
        $role->syncPermissions($request->validated('permission_ids', []));

        return redirect()->route('roles.index')->with('success', 'نقش و دسترسی‌های آن به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Role $role, AccessGuard $guard): RedirectResponse
    {
        $guard->ensureRoleCanChange($request->user(), $role);
        abort_if($role->is_system, 403, 'نقش سیستمی قابل حذف نیست.');
        $role->delete();

        return redirect()->route('roles.index')->with('success', 'نقش حذف شد.');
    }
}
