<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    public function update(User $user, Complaint $complaint): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Complaint $complaint): bool
    {
        return $user->role === 'super_admin';
    }
}
