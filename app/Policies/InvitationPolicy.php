<?php

namespace App\Policies;

use App\User;
use App\Course;
use App\Invitation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class InvitationPolicy
{
    use HandlesAuthorization;

    public function emailInvitation(User $user, Invitation $invitation, Course $course)
    {

        return ($user->email !=='commons@libretexts.org') && $course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to invite graders to this course.');

    }
}
