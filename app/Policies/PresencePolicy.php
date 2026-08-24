<?php

namespace App\Policies;

use App\Models\Presence;
use App\Models\User;

class PresencePolicy
{
    public function viewOverview(User $user): bool
    {
        return $user->hasFullFrontendVisibility();
    }

    public function delete(User $user, Presence $presence): bool
    {
        return $user->id === $presence->user_id;
    }
}
