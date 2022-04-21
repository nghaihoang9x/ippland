<?php



namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
class ApiController extends Controller
{
    public function sendError($message, $httpCode = 200, $errorLogic = null)
    {
        header('Access-Control-Allow-Origin: *');
        header('Content-type: application/json');
        header('X-Robots-Tag: noindex');
        header_remove("Set-Cookie");

        $httpCode=(int)$httpCode;

        if($httpCode == 400){
            header('HTTP/1.0 400 Bad request', true);
        }
        if($httpCode == 401){
            header('HTTP/1.0 401 Unauthorized', true);
        }
        if($httpCode == 404){
            header('HTTP/1.0 409 Not Found', true);
        }
        if($httpCode == 405){
            throw new CHttpException($httpCode, $message);
        }
        if($httpCode == 409){
            header('HTTP/1.0 409 Conflict', true);
        }
        // no error HTTP, but has error logic.
        if($httpCode == 200){}

        $result['error'] = $message;
        $result['detail'] = $errorLogic;
        return response()->json($result, $httpCode);
    }

    public function responseSuccess($result, $httpCode = 200)
    {
        header('Content-type: application/json');
        header('X-Robots-Tag: noindex');
        header_remove("Set-Cookie");

        if($httpCode == 201){
            header("Status: 201 Created");
        }
        else{
            header("Status: 200 OK");
        }
        return response()->json($result, \Illuminate\Http\Response::HTTP_OK);
    }
}