<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Spatie\Permission\Models\Role as SpatieRole;

#[Fillable(['name', 'guard_name', 'title', 'is_system'])]
class Role extends SpatieRole
{
    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }
}
