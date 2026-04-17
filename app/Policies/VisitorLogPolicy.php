<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisitorLog;

class VisitorLogPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'security']);
    }

    public function view(User $user, VisitorLog $log): bool
    {
        return in_array($user->role, ['admin', 'security']);
    }

    public function create(User $user): bool
    {
        return false; // Auto-generated only
    }

    public function update(User $user, VisitorLog $log): bool
    {
        return false; // Read-only
    }

    public function delete(User $user, VisitorLog $log): bool
    {
        return $user->role === 'admin';
    }
}
