<?php

namespace App\Http\Traits;

trait GetAuthenticUser
{

    public function authenticatedUser()
    {
        return json_encode($this->getPermissions());
    }

    public function objPermissions()
    {
        return (object) $this->getPermissions();
    }

    private function getPermissions()
    {
        return [
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'users' => auth()->user(),
        ];
    }
}
