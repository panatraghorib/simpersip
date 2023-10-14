<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ResponseApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Repositories\Profile\ProfileRepository;
use Illuminate\Http\JsonResponse;

class UserProfileController extends Controller
{
    // use Authorizable;

    private ProfileRepository $profileRepo;

    public function __construct(ProfileRepository $profileRepo)
    {
        $this->profileRepo = $profileRepo;
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->profileRepo->getProfile(auth()->user()->id),
        ]);
    }

    public function update(ProfileUpdateRequest $request)
    {
        try {
            $data = (Object) $request->validated();

            $profileUpdated = $this->profileRepo->updateProfile($data);
            $this->profileRepo->UpdateImageProfile($data);

            return ResponseApi::success();

        } catch (\exception$e) {
            return ResponseApi::failed($e);
        }
    }

}
