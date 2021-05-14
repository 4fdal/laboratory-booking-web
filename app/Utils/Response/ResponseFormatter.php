<?php

namespace App\Utils\Response;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class ResponseFormatter
{
    /**
     * @param array|object $data
     * @param Number       $http_status
     */
    public static function send(array $data = null, $http_status = 200)
    {
        return response($data, $http_status);
    }

    public static function otherFailedResponse($message, $data, $error, $http_status = 400)
    {
        return response([
            'data' => $data,
            'message' => $message,
            'error' => $error,
        ], $http_status);
    }

    public static function success($message = null, $data = null, $errors = [], $something = [])
    {
        return self::send(array_merge([
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ], $something));
    }

    /**
     * @param string $error
     *
     * @return ['message' => String, 'data' => Array|Object, 'errors' => Array]
     */
    public static function failed($error = null)
    {
        $response = [];
        $response['message'] = null;
        $response['errors'] = [];
        $response['data'] = (object) [];
        $http_status = 500;

        if ($error instanceof ValidationException) {
            $response['message'] = $error->getMessage();
            $response['errors'] = $error->errors();
            $http_status = 422;
        } elseif ($error instanceof QueryException) {
            $errors = [];
            if (env('APP_ENV', 'local') != 'production') {
                $errors['code'][] = $error->getCode();
                $errors['sql'][] = $error->getSql();
                $errors['bindings'][] = $error->getBindings();
            }
            $response['message'] = $error->getMessage();
            $response['errors'] = $errors;
            $http_status = 400;
        } elseif ($error instanceof Exception) {
            $response['message'] = $error->getMessage();
            $http_status = 400;
        } else {
            $response['message'] = $error;
            $http_status = 400;
        }

        return self::send($response, $http_status);
    }
}
