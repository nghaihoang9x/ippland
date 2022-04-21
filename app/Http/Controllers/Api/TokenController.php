<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\Collection;
use App\Models\Setting;
use App\Models\User;
use App\Models\Staff;
use App\Models\UserBox;
use App\Http\Controllers\Api\ApiController;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Wishlist;

class TokenController extends ApiController
{
    public function getToken($token = '')
    {
        $collectionModel = new Collection();
        $settingModel = new Setting();
        $user = User::checkToken();
        if ($user && $user != 'expired'){
            $cartToken = Cart::firstOrCreate(['user_id' => $user->_id]);
            $wishlistModel = new Wishlist();
            $wishlist = $wishlistModel->listWishlist(['user_id' => $user->_id]);
            $response['wishlist_count'] = isset($wishlist['total']) ? $wishlist['total'] : 0;
        }else{
            $cartToken = Cart::firstOrCreate(['device_token' => $token]);
        }
        $collection = $collectionModel->listCollection(['available' => true], 100);

        $response['device_token'] = $cartToken->device_token;
        $response['collections'] = isset($collection['data']) ? $collection['data'] : [];
        $response['setting_general'] = $settingModel->general();

        return $this->responseSuccess($response);
    }

    public function getUserByToken($token){
        if(empty($token) && !$token){
            return $this->sendError(true, \Illuminate\Http\Response::HTTP_BAD_REQUEST);
        }
        $user = User::checkToken();
        return $this->responseSuccess($user);
    }

    public function verify() {
        $staffs_token = Staff::checkToken();
        if ($staffs_token == 'expired' || !$staffs_token) {
            $response['expired'] = true;
        } else {
            $response['expired'] = false;
        }

        return $this->responseSuccess($response);
    }
}
