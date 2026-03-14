<?php

namespace App\Policies;

use App\Models\JeopardyBoard;
use App\Models\User;

class JeopardyBoardPolicy
{
    public function view(?User $user, JeopardyBoard $board): bool
    {
        return $board->is_public || ($user && $user->id === $board->user_id);
    }

    public function update(User $user, JeopardyBoard $board): bool
    {
        return $user->id === $board->user_id;
    }

    public function delete(User $user, JeopardyBoard $board): bool
    {
        return $user->id === $board->user_id;
    }
}
