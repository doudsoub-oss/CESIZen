<?php

namespace App\Policies;

use App\Models\ResultInterpretation;
use App\Models\User;

class ResultInterpretationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ResultInterpretation $interpretation): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ResultInterpretation $interpretation): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ResultInterpretation $interpretation): bool
    {
        return $user->isAdmin();
    }
}
