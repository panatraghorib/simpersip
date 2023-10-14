<?php

namespace App\Listeners\Backend\UserLoggedIn;

use Carbon\Carbon;
use App\Events\Backend\UserLoggedIn;

class UserLoggedInUpdateProfileData
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UserLoggedIn  $event
     * @return void
     */
    public function handle(UserLoggedIn $event)
    {
        try {

            $user = $event->user;
            $request = $event->request;
            $userProfile = $user->profile;

            /*
             * Updating user profile data after successful login
             */

            $userProfile->last_login = Carbon::now();
            $userProfile->last_ip = $request->getClientIp();
            $userProfile->login_count = $userProfile->login_count + 1;
            $userProfile->save();
            
        } catch (\Exception$e) {
            logger()->error($e);
        }

        logger('User Login Success. Name: ' . $user->name . ' | Id: ' . $user->id . ' | Email: ' . $user->email . ' | Username: ' . $user->username . ' IP:' . $request->getClientIp() . ' | UpdateProfileLoginData');

    }
}
