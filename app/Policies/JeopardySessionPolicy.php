<?php

namespace App\Policies;

use App\Models\JeopardySession;
use App\Models\User;

class JeopardySessionPolicy
{
    public function host(User $user, JeopardySession $session): bool
    {
        return $user->id === $session->host_id;
    }
}
