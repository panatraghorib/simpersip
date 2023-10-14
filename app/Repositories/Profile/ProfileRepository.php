<?php

namespace App\Repositories\Profile;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use App\Repositories\Profile\ProfileInterface;

class ProfileRepository implements ProfileInterface
{

    protected $user;
    protected $profile;

    public function __construct(User $user, UserProfile $profile)
    {
        $this->user = $user;
        $this->profile = $profile;
    }

    public function getProfile($id)
    {
        $profile = $this->profile->whereUserId($id)->first();
        // $profile->avatar = $profile->avatar ? URL::to($profile->avatar) : null;
        $profile->avatar = $profile->avatar ?
        'data:image/jpeg;base64,' . base64_encode(file_get_contents($profile->avatar)) : null;
        return $profile;
        // return $this->profile->whereUserId($id)->first();
    }

    public function updateProfile($params)
    {
        return $this->profile->whereUserId($params->user_id)->update([
            'email' => $params->email,
            'username' => $params->username,
            'first_name' => $params->first_name,
            'last_name' => $params->last_name,
            'mobile' => $params->mobile,
            'gender' => $params->gender,
            'address' => $params->address,
            'bio' => $params->bio,
            'date_of_birth' => $params->date_of_birth,
            'url_website' => $params->url_website,
            'url_facebook' => $params->url_facebook,
            'url_instagram' => $params->url_instagram,
        ]);
    }

    public function UpdateImageProfile($params)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $params->avatar, $type)) {
            $image = substr($params->avatar, strpos($params->avatar, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                throw new \Exception('invalid image type');
            }

            $image = str_replace(' ', '+', $image);
            $image = base64_decode($image);

            if ($image === false) {
                throw new \Exception('base64_decode failed');
            }


        } else {
            throw new \Exception('did not match data URI with image data');
        }

        // $fileName = 'profile-avatar-' . $params->id . "-" . Str::random() . '.' . $type;
        $fileName = 'profile-avatar-' . encode_id($params->id, 5) . '.' . $type;

        $mainDir = 'storage/photos/profiles/';
        $relativePath = $mainDir . $fileName;

        if (!File::exists($absolutePath = public_path($mainDir))) {
            File::makeDirectory($absolutePath, 0755, true);
        }
        file_put_contents($relativePath, $image);

        // Create Thumbs Image
        $thumbsDir = 'storage/photos/profiles/thumbs/';
        $ralativeThumbsPath = $thumbsDir . $fileName;

        if (!File::exists($absoluteThumbsPath = public_path($thumbsDir))) {
            File::makeDirectory($absoluteThumbsPath, 0755, true);
        }

        Image::make($image)->resize(100, 100)
            ->save($ralativeThumbsPath);

        $this->profile->whereUserId($params->user_id)->update([
            'avatar' => $ralativeThumbsPath,
        ]);
    }

    public function getCurrentPassword($id)
    {
        return $this->model->select('password')->where('id', $id)->first();
    }

    public function updatePassword($params, $id)
    {
        return $this->model->where('id', $id)->update([
            'password' => bcrypt($params->password),
        ]);

    }


}
