<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            // Acceptation de la politique de confidentialité, horodatée (L08).
            'privacy' => ['accepted'],
        ], [
            'privacy.accepted' => __('Vous devez accepter la politique de confidentialité.'),
        ])->validate();

        // Set role/active explicitly so the freshly-created in-memory instance
        // Fortify logs in is already authoritative — otherwise `is_active` is
        // null on the post-registration request and `EnsureUserIsActive`
        // logs the new user straight back out.
        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => Role::User,
            'is_active' => true,
            'privacy_accepted_at' => now(),
        ]);
    }
}
