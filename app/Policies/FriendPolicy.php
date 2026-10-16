<?php

namespace App\Policies;

use App\Models\Friend;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FriendPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Friend $friend): Response
    {
        if ($friend->user_id !== $user->id) {
            return Response::deny('You do not have permission for this access');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Friend $friend): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Friend $friend): Response
    {
        if ($friend->user_id !== $user->id) {
            return Response::deny('You do not have permission for this access');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Friend $friend): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Friend $friend): bool
    {
        return false;
    }
}
