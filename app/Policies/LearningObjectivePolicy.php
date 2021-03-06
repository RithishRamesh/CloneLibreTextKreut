<?php

namespace App\Policies;


use App\Traits\CommonPolicies;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class LearningObjectivePolicy
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
        return $this->IsNotStudent($user)
            ? Response::allow()
            : Response::deny('You are not allowed to create Learning Objectives.');

    }

    public function viewAny(User $user)
    {
        return $this->IsNotStudent($user)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve Learning Objectives from the database.');

    }

}
