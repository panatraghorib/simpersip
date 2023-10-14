<?php

namespace App\Listeners\Backend\UserCreated;

use App\Models\UserProfile;
use App\Events\Backend\UserCreated;
use Illuminate\Support\Facades\Log;

class UserCreatedProfileCreate
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
     * @param  \App\Events\UserCreated  $event
     * @return void
     */
    public function handle(UserCreated $event)
    {
        $user = $event->user;

        $profile = new UserProfile();
        $profile->user_id = $user->id;
        $profile->name = $user->name;
        $profile->username = $user->username;
        $profile->email = $user->email;
        $profile->mobile = $user->phone;
        $profile->avatar = $user->avatar;
        $profile->blocked_status = $user->blocked_status;
        $profile->save();

        Log::info('UserCreatedProfileCreate: ' . $profile->name . '(Id:' . $profile->user_id . ')');

        // Clear Cache
        \Artisan::call('cache:clear');
    }
}
