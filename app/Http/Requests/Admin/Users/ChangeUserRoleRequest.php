<?php

namespace App\Http\Requests\Admin\Users;

use App\Enums\Role;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');
        $role = $this->resolveRole();

        if ($role === null) {
            return true; // Defer to validation errors below.
        }

        return $this->user()?->can('changeRole', [$target, $role]) ?? false;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in([Role::User->value, Role::Admin->value])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->resolveRole() === null && ! $validator->errors()->has('role')) {
                    $validator->errors()->add('role', __('Rôle invalide.'));
                }
            },
        ];
    }

    private function resolveRole(): ?Role
    {
        return Role::tryFrom((string) $this->input('role'));
    }
}
