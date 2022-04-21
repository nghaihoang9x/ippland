<?php

namespace App\Http\Controllers\Api;

use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Customer;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;

class FavoriteController extends ApiController
{
    public function getProductByFavorite()
    {
        JWTAuth::parseToken();
        $token = JWTAuth::getToken();
        if (!$token) return $this->sendError("Forbidden", 401, "Token expired");
        $user = JWTAuth::toUser($token);
        if (!$user) {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "User Not Found");
        }

        $data['user_id'] = $user->_id;
        $data['type'] = 'product';
        $favorites = Favorite::getFavorites($data);
        $arr_product_id = $favorites->pluck('favorite_value')->toArray();
        $list_product = Product::whereIn('_id', $arr_product_id)->get();
        if ($list_product) {

            return $this->responseSuccess($list_product);
        }

        return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Customer Not Found");
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        JWTAuth::parseToken();
        $token = JWTAuth::getToken();
        if (!$token) return $this->sendError("Forbidden", 401, "Token expired");
        $user = JWTAuth::toUser($token);
        if (!$user) {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "User Not Found");
        }
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }
        $data['user_id'] = $user->_id;
        $result = Favorite::saveFavorite($data);
        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;

            return $this->responseSuccess($res);
        } else {

            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Customer Not Found");
        }
    }

    public function delete($id)
    {
        JWTAuth::parseToken();
        $token = JWTAuth::getToken();
        if (!$token) return $this->sendError("Forbidden", 401, "Token expired");
        $user = JWTAuth::toUser($token);
        if (!$user) {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "User Not Found");
        }
        $res = Favorite::deleteFavorite($user->_id, $id);
        if ($res) {
            return $this->responseSuccess($res);
        }

        return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Customer Not Found");
    }
}
