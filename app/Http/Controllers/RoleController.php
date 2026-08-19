<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Activity\ActivityLogger;
use App\Support\AccessGuard;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('roles.index', ['roles' => Role::query()->withCount(['users', 'permissions'])->orderBy('title')->paginate(app(SettingsService::class)->paginationPerPage())]);
    }

    public function create(): View
    {
        return view('roles.create', ['permissionGroups' => Permission::query()->orderBy('group')->orderBy('title')->get()->groupBy('group')]);
    }

    public function store(StoreRoleRequest $request, ActivityLogger $activity): RedirectResponse
    {
        $role = Role::query()->create($request->safe()->except('permission_ids') + ['guard_name' => 'web']);
        $permissionIds = $request->validated('permission_ids', []);
        $role->syncPermissions($permissionIds);

        $activity->record(
            'role.created',
            $role,
            $request->user(),
            'نقش جدید ایجاد شد.',
            new: [
                'name' => $role->name,
                'title' => $role->title,
                'permission_ids' => collect($permissionIds)->map(fn ($id) => (int) $id)->sort()->values()->all(),
            ],
        );

        return redirect()->route('roles.index')->with('success', 'نقش با موفقیت ایجاد شد.');
    }

    public function edit(Request $request, Role $role, AccessGuard $guard): View
    {
        $guard->ensureRoleCanChange($request->user(), $role);
        $role->load('permissions');

        return view('roles.edit', ['role' => $role, 'permissionGroups' => Permission::query()->orderBy('group')->orderBy('title')->get()->groupBy('group')]);
    }

    public function update(
        UpdateRoleRequest $request,
        Role $role,
        AccessGuard $guard,
        ActivityLogger $activity,
    ): RedirectResponse {
        $guard->ensureRoleCanChange($request->user(), $role);
        $role->load('permissions');

        $before = [
            'name' => $role->name,
            'title' => $role->title,
            'permission_ids' => $role->permissions->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
        ];

        $data = $request->safe()->except('permission_ids');
        if ($role->is_system) {
            unset($data['name']);
        }

        $permissionIds = $request->validated('permission_ids', []);
        $role->update($data);
        $role->syncPermissions($permissionIds);
        $role->refresh()->load('permissions');

        $after = [
            'name' => $role->name,
            'title' => $role->title,
            'permission_ids' => $role->permissions->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
        ];

        $changes = $activity->changed($before, $after);
        if ($changes['old'] !== [] || $changes['new'] !== []) {
            $activity->record(
                'role.updated',
                $role,
                $request->user(),
                'نقش و دسترسی‌های آن ویرایش شد.',
                $changes['old'],
                $changes['new'],
            );
        }

        return redirect()->route('roles.index')->with('success', 'نقش و دسترسی‌های آن به‌روزرسانی شد.');
    }

    public function destroy(
        Request $request,
        Role $role,
        AccessGuard $guard,
        ActivityLogger $activity,
    ): RedirectResponse {
        $guard->ensureRoleCanChange($request->user(), $role);
        abort_if($role->is_system, 403, 'نقش سیستمی قابل حذف نیست.');

        $role->load('permissions');
        $activity->record(
            'role.deleted',
            $role,
            $request->user(),
            'نقش حذف شد.',
            old: [
                'name' => $role->name,
                'title' => $role->title,
                'permission_ids' => $role->permissions->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ],
        );

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'نقش حذف شد.');
    }
}
