<?php

namespace App\Policies;

use App\Traits\CommonPolicies;
use App\User;
use App\LearningTree;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class LearningTreePolicy
{
    use CommonPolicies;
    /**
     * Determine whether the user can store the learning objective
     *
     * @param \App\User $user
     * @param \App\Score $score
     * @return mixed
     */
    public function store(User $user)
    {
        return ((int) $user->role === 2)
            ? Response::allow()
            : Response::deny('You are not allowed to save Learning Trees.');

    }

    public function update(User $user, LearningTree $learningTree)
    {
        return ((int) $learningTree->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to update this Learning Tree.');

    }

    public function index(User $user) {
        return ((int) $user->role === 2)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve all of your Learning Trees.');

    }
}
