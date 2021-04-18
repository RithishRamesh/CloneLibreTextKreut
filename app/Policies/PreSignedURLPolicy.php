<?php

namespace App\Policies;

use App\Assignment;
use App\PreSignedURL;
use App\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class PreSignedURLPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function preSignedURL(User $user, PreSignedURL $preSignedURL, Assignment $assignment, String $upload_file_type)
    {

        $has_access= false;
        switch ($upload_file_type){
            case('solution'):
                $has_access =$user->role === 2;
                break;
            case('submission'):
                $has_access = $user->role === 3 && $assignment->course->enrollments->contains('user_id', $user->id);
        }


        return  $has_access
            ? Response::allow()
            : Response::deny("You are not allowed to upload $upload_file_type files.");

    }
}
