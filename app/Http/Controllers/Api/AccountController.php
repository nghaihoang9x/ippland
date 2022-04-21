<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Api\ApiController;
use App\Models\UserAddresses;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use MongoDB\Driver\Session;

class AccountController extends ApiController
{
//    public function update()
//    {
//        $data = [];
//        $post = request()->all();
//
//        $user = User::checkToken();
//        if ($user == false || $user == 'expired') {
//            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error");
//        }
//        if (!empty(request()->get('password')) &&
//            !empty(request()->get('old_password'))) {
//            if (!Hash::check($post['old_password'], $user->password)) {
//                return $this->responseSuccess(['success' => false, 'message' => 'Mật khẩu hiện tại không chính xác']);
//            }
//        }
//        unset($post['_token']);
//        $post['user_id'] = $user->_id;
//        $user->updateUser($post);
//        return $this->responseSuccess(['success' => true, 'message' => 'Cập nhật thông tin thành công!']);
//
//    }

    public function addresses()
    {
        $user = User::checkToken();
        if ($user == false || $user == 'expired') {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error");
        }

        $addresses = $user->getAddresses();
        return $this->responseSuccess($addresses);
    }

    public function addAddresses()
    {
        $user = User::checkToken();

        if ($user == false || $user == 'expired') {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error");
        }

        $post = request()->all();
        $post['user_id'] = $user['_id'];
        if (!array_key_exists('default', $post)) {
            $post['default'] = 0;
        } else {
            $user->addresses()->update(['default' => 0]);
        }
        $address = UserAddresses::forceCreate($post);

        return $this->responseSuccess($address);
    }

    public function updateAddresses($id)
    {
        $user = User::checkToken();

        if ($user == false || $user == 'expired') {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error");
        }

        $post = request()->all();
        unset($post['user_id']);
        unset($post['_token']);
        if (!array_key_exists('default', $post))
            $post['default'] = 0;
        else {
            $user->addresses()->update(['default' => 0]);
        }
        $address = $user->addresses->where('_id', $id)->first()->update($post);

        return $this->responseSuccess($address);
    }

    public function getAddress($id)
    {
        $user = User::checkToken();
        if ($user == false || $user == 'expired') {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error");
        }

        $address = UserAddresses::find($id);
        return $this->responseSuccess($address);
    }

    public function deleteAddresses($id)
    {
        $user = User::checkToken();
        if ($user == false || $user == 'expired') {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error");
        }

        $address = UserAddresses::find($id);
        $address->delete();
        return $this->responseSuccess('');
    }


    public function orders(Request $request)
    {
        $get = $request->all();
        $limit = isset($get['limit']) ? $get['limit'] : '';
        $user = User::checkToken();
        if ($user == false || $user == 'expired') {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error");
        }
        $addresses = $user->getOrders($limit);
        return $this->responseSuccess($addresses);
    }

    public function wishlist(Request $request)
    {
        $get = $request->all();
        $user = User::checkToken();
        if ($user == false || $user == 'expired') {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error");
        }
        $get['user_id'] = $user->_id;
        $wishlist = new Wishlist();
        $res = $wishlist->listWishlist($get);
        return $this->responseSuccess($res);
    }

    public function order()
    {
        $user = User::checkToken();
        if ($user == false || $user == 'expired') {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error");
        }
        $id = request()->get('id');
        $addresses = $user->getOrder($id);
        return $this->responseSuccess($addresses);
    }

    /**
     * @return mixed
     */
    public function findAccount()
    {
        $user = User::checkToken();

        if ($user) {

            return $this->responseSuccess($user);
        }

        return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Bad Request");
    }

    public function updateAccount()
    {
        $post = request()->all();
        $user = User::checkToken();
        if ($user == false || $user == 'expired') {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error");
        }
        if (request()->has('password')) {
            if (!Hash::check($post['old_password'], $user->password)) {
                return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Sai mật khẩu hiện tại");
            }
        }
        $post['user_id'] = $user['_id'] . '';
        $user = $user->updateUser($post);

        return $this->responseSuccess($user);
    }

    /**
     * @param $code
     * @return mixed
     */
    public function active($code)
    {
        $user = User::activeCode($code);
        if ($user) {

            return $this->responseSuccess($user);
        }

        return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Tai khoan khong ton tai");
    }

    public function forgotPassword()
    {

    }
}
