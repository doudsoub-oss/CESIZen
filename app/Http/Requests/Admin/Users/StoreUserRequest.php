<?php

namespace App\Http\Requests\Admin\Users;

use App\Concerns\PasswordValidationRules;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class StoreUserRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => $this->passwordRules(),
            'role' => ['required', $this->allowedRoles()],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Regular admins can only create `user` accounts; super-admins can
     * additionally create `admin` accounts. Nobody creates a super-admin
     * via the UI — that role is reserved for the DB seed.
     */
    private function allowedRoles(): In
    {
        $allowed = $this->user()?->isSuperAdmin()
            ? [Role::User->value, Role::Admin->value]
            : [Role::User->value];

        return Rule::in($allowed);
    }
}
