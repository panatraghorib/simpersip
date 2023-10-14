<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;

class TestController extends Controller
{

    public function roles()
    {
        $roles = Role::get();
        $roles = User::find(10)->roles()->orderBy('name')->get();
        $user = User::query()->with(['roles' => function ($query) {
            $query->select('id');
        }])->where('id', 10)->first();

        dump($user);
    }
}
