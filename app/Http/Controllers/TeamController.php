<?php

namespace App\Http\Controllers;

use App\Http\Requests\Team\StoreTeamMemberRequest;
use App\Http\Requests\Team\UpdateTeamMemberRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Support\AccessGuard;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $users = User::query()->with('roles')->when($search, function ($query) use ($search) {
            $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
        })->latest()->paginate(app(SettingsService::class)->paginationPerPage())->withQueryString();

        return view('team.index', compact('users', 'search'));
    }

    public function create(): View
    {
        return view('team.create', ['roles' => Role::query()->orderBy('title')->get()]);
    }

    public function store(StoreTeamMemberRequest $request, AccessGuard $guard, ActivityLogger $activity): RedirectResponse
    {
        $data = $request->safe()->except(['role_ids']);
        $roleIds = $request->user()->can('team.assign_roles') ? $request->validated('role_ids', []) : [];
        $guard->ensureRolesAssignable($request->user(), $roleIds);

        $user = User::query()->create($data);
        $user->syncRoles($roleIds);

        $activity->record(
            'team.user_created',
            $user,
            $request->user(),
            'عضو جدید تیم ایجاد شد.',
            new: [
                'name' => $user->name,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'role_ids' => collect($roleIds)->map(fn ($id) => (int) $id)->values()->all(),
            ],
        );

        return redirect()->route('team.index')->with('success', 'عضو تیم با موفقیت ایجاد شد.');
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('team.edit', ['user' => $user, 'roles' => Role::query()->orderBy('title')->get()]);
    }

    public function update(
        UpdateTeamMemberRequest $request,
        User $user,
        AccessGuard $guard,
        ActivityLogger $activity,
    ): RedirectResponse {
        $user->load('roles');

        $before = [
            'name' => $user->name,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
        ];
        $beforeRoleIds = $user->roles->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();

        $roleIds = $request->user()->can('team.assign_roles')
            ? $request->validated('role_ids', [])
            : $user->roles()->pluck('roles.id')->all();

        $guard->ensureUserCanChange($request->user(), $user, $request->boolean('is_active'), $roleIds);

        $data = $request->safe()->except(['role_ids', 'password']);
        $passwordChanged = $request->filled('password');
        if ($passwordChanged) {
            $data['password'] = $request->validated('password');
        }

        $user->update($data);
        $user->syncRoles($roleIds);
        $user->refresh()->load('roles');

        $after = [
            'name' => $user->name,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
        ];

        $changes = $activity->changed($before, $after);
        if ($changes['old'] !== [] || $changes['new'] !== []) {
            $activity->record(
                'team.user_updated',
                $user,
                $request->user(),
                'اطلاعات عضو تیم ویرایش شد.',
                $changes['old'],
                $changes['new'],
            );
        }

        $afterRoleIds = $user->roles->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        if ($beforeRoleIds !== $afterRoleIds) {
            $activity->record(
                'team.role_changed',
                $user,
                $request->user(),
                'نقش‌های عضو تیم تغییر کرد.',
                ['role_ids' => $beforeRoleIds],
                ['role_ids' => $afterRoleIds],
            );
        }

        if ($passwordChanged) {
            $activity->record(
                'team.password_changed',
                $user,
                $request->user(),
                'رمز عبور عضو تیم توسط مدیر تغییر کرد.',
            );
        }

        return redirect()->route('team.index')->with('success', 'اطلاعات عضو تیم به‌روزرسانی شد.');
    }

    public function destroy(
        Request $request,
        User $user,
        AccessGuard $guard,
        ActivityLogger $activity,
    ): RedirectResponse {
        $guard->ensureUserCanDelete($request->user(), $user);

        $user->load('roles');
        $activity->record(
            'team.user_deleted',
            $user,
            $request->user(),
            'عضو تیم حذف شد.',
            old: [
                'name' => $user->name,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'role_ids' => $user->roles->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ],
        );

        $user->delete();

        return redirect()->route('team.index')->with('success', 'عضو تیم حذف شد.');
    }
}
