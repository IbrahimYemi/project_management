<?php

namespace App\Traits;

trait ApiResponseTrait
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function sendResponse($result, $message = 'Operation successful!', $code = 200, $token = null)
    {
        $result = [
            'success' => true,
            'data' => $result,
            'message' => $message,
        ];
        if ($token) {
            $result['token'] = $token;
        }
        return response()->json($result, $code);
    }
  
    /**
     * return error response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function sendError(string $error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];
  
        if(!is_array($errorMessages)){
            $errorMessages = ['error' => $errorMessages];
        }
  
        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }
  
        return response()->json($response, $code);
    }
}