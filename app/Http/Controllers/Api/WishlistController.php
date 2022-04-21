<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Wishlist;
use App\Http\Controllers\Api\ApiController;

class WishlistController extends ApiController
{
    public function store(Request $request)
    {
        $user = User::checkToken();
        if ($user == false || $user == 'expired'){
            return $this->responseSuccess(['success' => false, 'message' => 'Bạn phải đăng nhập để sử dụng chức năng này']);
        }
        $post = $request->all();

        $variant_id = isset($post['variant_id']) ? $post['variant_id'] : '';
        $exits = Wishlist::where('variant_id', $variant_id)->where('user_id', $user->_id)->first();
        if($exits){
            return $this->responseSuccess(['success' => true, 'message' => 'Sản phẩm đã được thêm vào Wishlist của bạn']);
        }

        $data = [];
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }
        $data['user_id'] = $user->_id;
        $ServiceModel = new Wishlist();
        $result = $ServiceModel->saveWishlist($data);
        if ($result) {
            $res['_id'] = $result;
            $res['success'] = true;
            $res['message'] = 'Sản phẩm đã được thêm vào Wishlist của bạn';
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $wishlist = new Wishlist();
            $wishlists = $wishlist->listWishlist(['user_id' => $user->_id]);
            $res['number_wishlist'] = isset($wishlists['total']) ? $wishlists['total'] : 0;
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Service Not Found");
        }

    }

    public function list(Request $request)
    {
        $get = $request->all();
        $ServiceModel = new Wishlist();
        $result = $ServiceModel->listWishlist($get);

        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function update(Request $request, $id)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }
        $ServiceModel = new Wishlist();
        $result = $ServiceModel->updateWishlist($data, $id);
        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Service Not Found");
        }
    }

    public function updateMultiple(Request $request)
    {
        $data = [];
        $post = $request->all();
        $ServiceModel = new Wishlist();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $ServiceModel->updateWishlist($data, $key);
        }

        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Service Not Found");
        }
    }

    public function delete(Request $request) {
        $ids = $request->get('ids');
        if ($ids) {
            $ids = explode(',', $ids);
            foreach ($ids as $_id) {
                Wishlist::find($_id)->delete();
            }
            $result['deleted_ids'] = $ids;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function search(Request $request) {
        $get = $request->all();
        $keyword = $request->get('keyword');
        if ($keyword) {

            $ServiceModel = new Wishlist();
            $result = $ServiceModel->listWishlist($get);
            if ($result) {
                //$result['response_time'] = microtime(true) - LARAVEL_START;
                return $this->responseSuccess($result);
            } else {
                $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
            }
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }
}
