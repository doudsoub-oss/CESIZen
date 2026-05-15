<?php

namespace App\Policies;

use App\Models\AnswerOption;
use App\Models\User;

class AnswerOptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, AnswerOption $option): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, AnswerOption $option): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, AnswerOption $option): bool
    {
        return $user->isAdmin();
    }
}
