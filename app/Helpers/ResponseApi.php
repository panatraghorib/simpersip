<?php
namespace App\Helpers;

use App\Exceptions\SingleException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ResponseApi
{
    private static function send($data, $httpStatus = 200)
    {
        $request = new Request;
        $response = ConvertCase::camel($data);
        if ($request->method() == 'GET') {
            $response = FileHandle::handle($response);
        }

        return response()->json($response, $httpStatus);
    }

    private static function sendRes($data, $httpStatus = 200)
    {
        $request = new Request;
        $response = ConvertCase::camel($data);
        // $response = $data;
        if ($request->method() == 'GET') {
            $response = FileHandle::handle($response);
        }

        return response()->json($response, $httpStatus);
    }

    // BLOCK TESTING
    private static function sending($data, $httpStatus = 200)
    {
        $request = new Request;
        $response = ($data);
        // if ($request->method() == 'GET') {
        //     $response = FileHandle::handle($response);
        // }

        return response()->json($response, $httpStatus);
    }

    public static function xsuccess($value = null)
    {
        $response = [];
        $response['message'] = __('api_response.200');
        $response['error'] = null;
        if (!is_null($value)) {
            $response['data'] = $value;
        }

        return self::sending($response);
    }
    // END BLOCK TESTING

    // Handle Success Request
    public static function success($value = null)
    {
        $response = [];
        $response['message'] = __('api_response.200');
        $response['error'] = null;
        if (!is_null($value)) {
            $response['data'] = $value;
        }

        return self::send($response);
    }

    public static function res($value = null)
    {
        $response = [];
        $response['message'] = __('api_response.200');
        $response['error'] = null;
        if (!is_null($value)) {
            $response['data'] = $value;
        }
        return self::sendRes($response);
        // return $response;
    }

    // Handle Failed Request
    public static function failed($error = null)
    {
        if (env('APP_ENV', 'local') != 'production') {
            Log::debug($error);
        }

        $response = [];
        $response['data'] = null;
        $response['message'] = null;
        $response['errors'] = [];

        $http_status = 500;

        if ($error instanceof ValidationException) {
            $response['message'] = __('api_response.400');
            $response['errors'] = $error->errors();
            $http_status = 400;
        } elseif ($error instanceof SingleException) {
            $response['message'] = $error->getMessage();
            $http_status = 400;
        } elseif ($error instanceof QueryException) {
            $errors = [];

            if (env('APP_ENV', 'local') != 'production') {
                $errors['code'][] = $error->getCode();
                $errors['sql'][] = $error->getSql();
                $errors['bindings'][] = $error->getBindings();
            }

            $response['message'] = $error->getMessage();
            $response['errors'] = $errors;
        } elseif ($error instanceof \Exception) {
            if (env('APP_DEBUG') == true) {
                $response['message'] = $error->getMessage();
                $response['errors'] = json_decode(json_encode($error->getTrace()));
            } else {
                $response['message'] = $error->getMessage();
            }
        } else {
            if (is_object($error) || is_array($error)) {
                $response['message'] = json_encode($error);
            } else {
                $response['message'] = $error;
            }
        }

        return self::send($response, $http_status);
    }

    public static function preconditionFailed($message = null)
    {
        $response['message'] = $message ?? __('api_response.412');
        $response['errors'] = null;
        $response['data'] = null;

        return self::send($response, 412);
    }

    public static function serviceUnavailable()
    {
        $response['message'] = __('api_response.503');
        $response['errors'] = null;
        $response['data'] = null;

        return self::send($response, 503);
    }

    public static function paymentRequired($message = null)
    {
        $response['message'] = $message ?? __('api_response.402');
        $response['errors'] = null;
        $response['data'] = null;

        return self::send($response, 402);
    }

    public static function forbidden()
    {
        $response['message'] = __('api_response.403');
        $response['errors'] = null;
        $response['data'] = null;

        return self::send($response, 403);
    }

    public static function unauthorized($message = null)
    {
        $response['message'] = $message ? $message : __('api_response.401');
        $response['errors'] = null;
        $response['data'] = null;

        return self::send($response, 401);
    }

    public static function onlyEntity($data = null, $permissions = null)
    {
        $response = [];
        $response['message'] = __('api_response.200');
        $response['data'] = $data;
        $response['errors'] = null;
        $response = json_decode(json_encode($response));

        return self::send($response);
    }

    public static function entity($data_type, $data = null, $permissions = null)
    {
        $response = [];
        $response['message'] = __('api_response.200');
        $response['data']['data_type'] = $data_type;
        $response['data']['entities'] = $data;
        $response['errors'] = null;
        $response = json_decode(json_encode($response));

        return self::send($response);
    }

}
