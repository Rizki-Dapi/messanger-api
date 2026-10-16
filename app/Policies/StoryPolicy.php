<?php

namespace App\Policies;

use App\Models\Story;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StoryPolicy
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
    public function viewViewers(User $user, Story $story): Response
    {
        if ((int) $story->user_id !== (int) $user->id) {
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
    public function update(User $user, Story $story): Response
    {
        if ($story->user_id !== $user->id) {
            return Response::deny('Your not the owwner of this story.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Story $story): Response
    {
        if ($story->user_id !== $user->id) {
            return Response::deny('Your not the owwner of this story.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Story $story): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Story $story): bool
    {
        return false;
    }
}
