<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class AccessGuard
{
    public function ensureRolesAssignable(User $actor, array $roleIds): void
    {
        if ($roleIds !== [] && ! $actor->can('team.assign_roles')) {
            throw new AuthorizationException('شما اجازه تخصیص نقش را ندارید.');
        }

        if ($actor->hasRole('super-admin')) {
            return;
        }

        if (Role::query()->whereKey($roleIds)->where('name', 'super-admin')->exists()) {
            throw new AuthorizationException('شما اجازه تخصیص نقش مدیر کل را ندارید.');
        }
    }

    public function ensureRoleCanChange(User $actor, Role $role): void
    {
        if ($role->name === 'super-admin' && ! $actor->hasRole('super-admin')) {
            throw new AuthorizationException('مدیریت نقش مدیر کل مجاز نیست.');
        }
    }

    public function ensureUserCanChange(User $actor, User $target, bool $willBeActive, array $roleIds): void
    {
        $this->ensureRolesAssignable($actor, $roleIds);

        if ($target->hasRole('super-admin') && ! $actor->hasRole('super-admin')) {
            throw new AuthorizationException('مدیریت حساب مدیر کل مجاز نیست.');
        }

        $keepsSuperAdmin = Role::query()->whereKey($roleIds)->where('name', 'super-admin')->exists();
        if ($target->is_active && $target->hasRole('super-admin') && (! $willBeActive || ! $keepsSuperAdmin)) {
            $this->ensureAnotherActiveSuperAdminExists($target);
        }
    }

    public function ensureUserCanDelete(User $actor, User $target): void
    {
        if ($target->hasRole('super-admin')) {
            if (! $actor->hasRole('super-admin')) {
                throw new AuthorizationException('حذف مدیر کل مجاز نیست.');
            }

            $this->ensureAnotherActiveSuperAdminExists($target);
        }
    }

    private function ensureAnotherActiveSuperAdminExists(User $target): void
    {
        $count = User::role('super-admin')->where('is_active', true)->whereKeyNot($target->getKey())->count();

        if ($count === 0) {
            throw new AuthorizationException('آخرین مدیر کل فعال را نمی‌توان حذف یا غیرفعال کرد.');
        }
    }
}
