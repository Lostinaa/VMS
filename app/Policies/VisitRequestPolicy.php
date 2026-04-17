<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisitRequest;

class VisitRequestPolicy
{
    /**
     * Admin and receptionist can see all visits.
     * Hosts see only visits assigned to them.
     * Security can view but not edit.
     * CXO PA can view VIP/executive visits.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'host', 'receptionist', 'security', 'cxo_pa']);
    }

    public function view(User $user, VisitRequest $visitRequest): bool
    {
        if (in_array($user->role, ['admin', 'receptionist', 'security', 'cxo_pa'])) {
            return true;
        }

        // Hosts can only view visits assigned to them
        return $user->role === 'host' && $visitRequest->host_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'receptionist', 'host']);
    }

    public function update(User $user, VisitRequest $visitRequest): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'receptionist') {
            return true;
        }

        // Hosts can only update their own visit requests
        return $user->role === 'host' && $visitRequest->host_id === $user->id;
    }

    public function delete(User $user, VisitRequest $visitRequest): bool
    {
        return $user->role === 'admin';
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === 'admin';
    }
}
