<?php

namespace App\Http\Controllers;

use App\Http\Requests\Team\StoreTeamMemberRequest;
use App\Http\Requests\Team\UpdateTeamMemberRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\AccessGuard;
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
        })->latest()->paginate(15)->withQueryString();

        return view('team.index', compact('users', 'search'));
    }

    public function create(): View
    {
        return view('team.create', ['roles' => Role::query()->orderBy('title')->get()]);
    }

    public function store(StoreTeamMemberRequest $request, AccessGuard $guard): RedirectResponse
    {
        $data = $request->safe()->except(['role_ids']);
        $roleIds = $request->user()->can('team.assign_roles') ? $request->validated('role_ids', []) : [];
        $guard->ensureRolesAssignable($request->user(), $roleIds);
        $user = User::query()->create($data);
        $user->syncRoles($roleIds);

        return redirect()->route('team.index')->with('success', 'عضو تیم با موفقیت ایجاد شد.');
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('team.edit', ['user' => $user, 'roles' => Role::query()->orderBy('title')->get()]);
    }

    public function update(UpdateTeamMemberRequest $request, User $user, AccessGuard $guard): RedirectResponse
    {
        $roleIds = $request->user()->can('team.assign_roles')
            ? $request->validated('role_ids', [])
            : $user->roles()->pluck('roles.id')->all();
        $guard->ensureUserCanChange($request->user(), $user, $request->boolean('is_active'), $roleIds);
        $data = $request->safe()->except(['role_ids', 'password']);
        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }
        $user->update($data);
        $user->syncRoles($roleIds);

        return redirect()->route('team.index')->with('success', 'اطلاعات عضو تیم به‌روزرسانی شد.');
    }

    public function destroy(Request $request, User $user, AccessGuard $guard): RedirectResponse
    {
        $guard->ensureUserCanDelete($request->user(), $user);
        $user->delete();

        return redirect()->route('team.index')->with('success', 'عضو تیم حذف شد.');
    }
}
