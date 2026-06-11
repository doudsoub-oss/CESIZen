<?php

namespace App\Policies;

use App\Models\User;

class AuditLogPolicy
{
    /**
     * Only administrators may read the audit trail. Stored values are already
     * redacted, so no further per-row gating is required.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
