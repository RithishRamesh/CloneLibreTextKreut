<?php

namespace App\Policies;

use App\Course;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    private $admins;

    public function __construct()
    {

    }

    private function isAdminWithCookie($user){
        $admins = ['adapt@libretexts.org','dlarsen@ucdavis.edu'];
        if (app()->environment('local', 'testing')){
            $admins[] = 'me@me.com';
        }

        $isValidEmail =  in_array(session()->get('original_email'),$admins);//get the original email since they may be in student view
        $isValidCookie  =isset(request()->cookie()['IS_ME']) && (request()->cookie()['IS_ME'] === config('myconfig.is_me_cookie'));

        return $isValidEmail && $isValidCookie;
    }
    public function getAll(User $user)
    {

        return $this->isAdminWithCookie($user)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve the users from the database.');
    }

    public
    function loginAs(User $user)
    {

        return $this->isAdminWithCookie($user)
            ? Response::allow()
            : Response::deny('You are not allowed to log in as a different user.');
    }



}
