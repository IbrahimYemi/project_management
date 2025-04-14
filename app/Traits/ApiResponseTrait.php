<?php

namespace App\Traits;

use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponseTrait
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function sendResponse($result, $message = 'Operation successful!', $code = 200, $token = [])
    {
        $result = [
            'success' => true,
            'data' => $result,
            'message' => $message,
        ];
        if ($token) {
            $result['token'] = $token['token'];
            $result['loginAt'] = $token['loginAt'];
        }
        return response()->json($result, $code);
    }

    public static function sendPaginatedResponse($resultCollection, $page, $perPage, $message = 'Operation successful!', $code = 200)
    {
        $paginated = new LengthAwarePaginator(
            $resultCollection->forPage($page, $perPage),
            $resultCollection->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
        
        return self::sendResponse($paginated, $message, $code);
    }
  
    /**
     * return error response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function sendError(string $error, $errorMessages = [], $code = 400)
    {
        $response = [
            'success' => false,
            'message' => $error,
            'statusCode' => $code,
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