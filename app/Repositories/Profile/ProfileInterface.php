<?php
namespace App\Repositories\Profile;

interface ProfileInterface
{
    public function getProfile($id);
    public function updateProfile($params);
    public function getCurrentPassword($params);
    public function updatePassword($params, $id);
    public function UpdateImageProfile($params);
}
