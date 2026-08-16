<?php

namespace App\Http\Requests\Team;

use App\Models\User;
use App\Rules\IranianMobile;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNormalizer::normalize($this->string('phone')->toString()),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', new IranianMobile, Rule::unique('users', 'phone')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return (new StoreTeamMemberRequest)->messages();
    }
}
