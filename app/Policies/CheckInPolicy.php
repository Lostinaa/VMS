<?php

namespace App\Policies;

use App\Models\CheckIn;
use App\Models\User;

class CheckInPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'receptionist', 'security']);
    }

    public function view(User $user, CheckIn $checkIn): bool
    {
        return in_array($user->role, ['admin', 'receptionist', 'security']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'receptionist']);
    }

    public function update(User $user, CheckIn $checkIn): bool
    {
        return in_array($user->role, ['admin', 'receptionist']);
    }

    public function delete(User $user, CheckIn $checkIn): bool
    {
        return $user->role === 'admin';
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === 'admin';
    }
}
