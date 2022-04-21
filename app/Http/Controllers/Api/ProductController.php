<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Common;
use App\Models\Staff;
use App\Models\Variant;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\Navigation;
use App\Models\NavigationItem;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Http\Controllers\Api\ApiController;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;


class ProductController extends ApiController
{
    public function store(Request $request)
    {
        JWTAuth::parseToken();
        $token = JWTAuth::getToken();
        if (!$token) return $this->sendError("Forbidden", 401, "Token expired");
        $user = JWTAuth::toUser($token);

        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }
        $staff = Staff::checkToken();
        if ($staff && $staff != 'expired' && $staff->role == 'admin') {
            $data['status'] = true;
        } else {
            $data['status'] = false;
        }
        $data['user_id'] = $user->id;

        $ProductModel = new Product();


        $result = $ProductModel->saveProduct($data);

        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Category Not Found");
        }

    }

    public function getBySlug($slug)
    {
        if (!$slug) {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }

        $ProductModel = new Product();
        $result = $ProductModel->getProductBySlug($slug);
        $result = Common::mapProjectSeoAliasDetail($result);
        if ($result) {
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
    }

    public function list(Request $request)
    {
        $get = $request->all();
        $ProductModel = new Product();
        $result = $ProductModel->listProduct($get);
        $result = Common::mapProjectSeoAliasArray($result);
        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }

    }

    public function update(Request $request, $id)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {

            $data[$key] = $value;
        }
        if (isset($data['status'])) {
            $status = $data['status'];
            if ($status == '0' || $status == 0) {
                $data['status'] = false;
            }
            if ($status == '1' || $status == 1) {
                $data['status'] = true;
            }
        }
        $ProductModel = new Product();
        $result = $ProductModel->updateProduct($data, $id);
        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Category Not Found");
        }
    }

    public function updateMultiple(Request $request)
    {
        $data = [];
        $post = $request->all();
        $ProductModel = new Product();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $ProductModel->updateProduct($data, $key);
        }

        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Category Not Found");
        }
    }

    public function delete(Request $request)
    {
        $ids = $request->get('ids');
        if ($ids) {
            $ids = explode(',', $ids);
            $productModel = new Product();
            foreach ($ids as $_id) {
                $data = ['deleted' => 1];
                $productModel->updateProduct($data, $_id);
            }
            $result['deleted_ids'] = $ids;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function getByCollection($slug)
    {
        $collection = Collection::where('seo_alias', $slug)->where('available', true)->first();
        if (!isset($collection->_id)) {
            return $this->sendError("Collection Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Collection Not Found");
        }
        $ProductModel = new Product();

        $products = $ProductModel->getProductByCollectionId($collection->_id, request());
        if ($products) {
            $result['success'] = true;
            $result['data'] = $products;
            $result['response_time'] = microtime(true) - LARAVEL_START;

            return $this->responseSuccess($result);
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function search(Request $request)
    {

        $get = $request->all();
        $ProductModel = new Product();
        $result = $ProductModel->listProduct($get);
        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
    }

    public function review($seo_alias)
    {
        $ProductModel = new Product();
        $result = $ProductModel->getProductBySlug($seo_alias);
        $user = User::checkToken();
        if ($result && $user) {
            $review = new Review;
            $review->title = request()->get('title');
            $review->comment = request()->get('comment');
            $review->point = request()->get('point');
            $review->product_id = (string)$result->_id;
            $review->user_id = (string)$user->_id;
            $review->save();
            return $this->responseSuccess($review);
        }

        return $this->sendError("Not Found 1", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found 2");
    }

    public function getBreadcrumb($res = [], $collection_id, $i = 0)
    {
        $collection_id = $collection_id ? $collection_id : request()->get('collection');


        $collection = Collection::find($collection_id);
        if ($collection) {
            $res[$collection->seo_alias] = $collection->title;
            $i++;
        }

        $collectionItem = CollectionItem::where('collection_id', $collection_id)->first();

        if (empty($collectionItem->parent_id)) {

            $res = array_reverse($res, true);
            return $this->responseSuccess($res);
        }

        return $this->getBreadcrumb($res, $collectionItem->parent_id, $i);
    }

    public function variants(Request $request)
    {
        $get = $request->all();
        $VariantModel = new Variant();
        $result = $VariantModel->listVariant($get);
        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            $result['request'] = $request->all();
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
    }

    public function home(Request $request)
    {
        $get = $request->all();
        $collectionModel = new Collection();
        $collections = $collectionModel->homeList();
        $res = [];
        if ($collections) {
            $res['collections'] = $collections;
            foreach ($collections as $collection) {
                $get['collection'] = $collection['_id'];
            }
        }

        return $this->responseSuccess($res);
    }

    public function publish(Request $request)
    {
        $post = $request->all();
        $ids = isset($post['ids']) ? $post['ids'] : '';
        if ($ids) {
            $ids = explode(',', $ids);

            foreach ($ids as $_id) {
                $product = Product::find($_id);
                if ($product) {
                    $product->status = isset($product->status) ? !$product->status : true;
                    $product->update();
                }
            }
            $result['hide_ids'] = $ids;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function available(Request $request)
    {
        $post = $request->all();
        $ids = isset($post['ids']) ? $post['ids'] : '';
        if ($ids) {
            $ids = explode(',', $ids);

            foreach ($ids as $_id) {
                $product = Product::find($_id);
                if ($product) {
                    $product->available = isset($product->available) ? !$product->available : true;
                    $product->update();
                }
            }
            $result['hide_ids'] = $ids;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }
}
