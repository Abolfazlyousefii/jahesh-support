<?php

namespace App\Http\Requests\Role;

use App\Models\Role;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends StoreRoleRequest
{
    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');
        $rules = parent::rules();
        $rules['name'] = ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('roles', 'name')->ignore($role)];

        return $rules;
    }
}
