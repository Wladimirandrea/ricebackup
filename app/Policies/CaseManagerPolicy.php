<?php
// app/Policies/CaseManagerPolicy.php

namespace App\Policies;

use App\Models\User;

class CaseManagerPolicy
{
    /**
     * Solo admin puede ver la lista de case managers
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Solo admin puede ver clientes de un case manager
     */
    public function viewClients(User $user): bool
    {
        return $user->role === 'admin';
    }
}