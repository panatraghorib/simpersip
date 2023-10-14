<?php

namespace App\Http\Controllers\Backend;

use Appstra;
use App\Events\Backend\UserLoggedIn;
use App\Helpers\AppConfig;
use App\Helpers\ResponseApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\UserVerification;
use Exception;
use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\Auth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use stdClass;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware(config('appstra.middleware.authenticate'),
            ['except' => ['register', 'login']]);
    }

    public function login(LoginRequest $request)
    {
        try {
            $remember = $request->get('remember', false);

            //Get credential by username or email
            $credentials = $request->getCredentials();

            if (!$token = auth()->attempt($credentials)) {
                return response()->json(['message' => __('auth.failed')], 401);
            }

            $shouldVerifyEmail = AppConfig::get('adminPanelVerifyEmail') == '1' ? true : false;

            if ($shouldVerifyEmail) {
                $user = auth()->user();
                if (is_null($user->email_verified_at)) {
                    $token = rand(111111, 999999);
                    $tokenLifetime = 5;
                    $expiredToken = date('Y-m-d H:i:s',
                        strtotime("+$tokenLifetime minutes",
                            strtotime(date("Y-m-d H:i:s"))));

                    $data = [
                        'user_id' => $user->id,
                        'verification_token' => $token,
                        'expired_at' => $expiredToken,
                        'count_incorrect' => 0,
                    ];

                    $user = UserVerification::firstOrCreate($data);
                    $this->sendVerificationToken(['user' => $user, 'token' => $token]);

                    return ResponseApi::success($user);
                }
            }

            $ttl = $this->getTTL($remember);
            $token = auth()->setTTL($ttl)->attempt($credentials);

            event(new UserLoggedIn($request, auth()->user()));

            activity('Authentication')
                ->causedBy(auth()->user() ?? null)
                ->withProperties(['attributes' => auth()->user()])
                ->log('Login has been success');

            return $this->respondWithToken($token, auth()->user(), $remember);

        } catch (JWTException $e) {
            return ResponseApi::failed($e);
        } catch (Exception $e) {
            return ResponseApi::failed($e);
        }
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function refreshToken()
    {
        try {
            $ttl = $this->getTTL();
            $token = auth()->setTTL($ttl)->refresh();
            return $this->respondWithToken($token, auth()->user());
        } catch (Exception $e) {
            return ResponseApi::failed($e);
        }
    }

    protected function respondWithToken($token, $user)
    {
        $obj = new stdClass();
        $obj->access_token = $token;
        $obj->token_type = 'bearer';
        $obj->user = $user;
        $obj->expires_in = auth()->factory()->getTTL();

        return ResponseApi::success($obj);
    }

    private function getTTL($remember = false)
    {
        $rememberLifetime = 60 * 24 * 30; // one month
        $ttl = env('APPSTRA_AUTH_TOKEN_LIFETIME', Appstra::getDefaultJwtTokenLifetime());

        if ($ttl != '') {
            $ttl = (int) $ttl;
        } else {
            $ttl = Appstra::getDefaultJwtTokenLifetime();
        }

        if ($remember && $ttl < $rememberLifetime) {
            $ttl = $rememberLifetime;
        }

        return $ttl;
    }

    public function sendVerificationToken($data)
    {
        // return Mail::to($data['user']['email'])->queue(new SendUserVerification($data));
    }

    public function logout()
    {
        try {
            auth()->logout();
            activity('Authentication')
                ->causedBy(auth()->user() ?? null)
                ->log('Logout has been success');
            return ResponseApi::success("Successfully logged out");

        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }
    }

}
