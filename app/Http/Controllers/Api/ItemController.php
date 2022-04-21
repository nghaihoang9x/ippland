<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Review;
use App\Models\User;
use App\Models\Navigation;
use App\Models\NavigationItem;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Http\Controllers\Api\ApiController;

class ItemController extends ApiController
{
    public function store(Request $request)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }

        $ItemModel = new Item();


        $result = $ItemModel->saveItem($data);

        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Category Not Found");
        }

    }

    public function getBySlug($slug) {
        if (!$slug) {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }

        $ItemModel = new Item();
        $result = $ItemModel->getItemBySlug($slug);

        if ($result) {
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
    }

    public function list(Request $request)
    {
        $ids = $request->get('ids');
        $limit = $request->get('limit');
        $keyword = $request->get('keyword');
        $price = $request->get('price');
        $brand = $request->get('brand');
        if ($ids) {
            $ids = explode(',', $ids);
        }
        $ItemModel = new Item();
        $result = $ItemModel->listItem($ids, $limit, $keyword, $price, $brand);
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

        $ItemModel = new Item();
        $result = $ItemModel->updateItem($data, $id);
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
        $ItemModel = new Item();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $ItemModel->updateItem($data, $key);
        }

        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Category Not Found");
        }
    }

    public function delete(Request $request) {
        $ids = $request->get('ids');
        if ($ids) {
            $ids = explode(',', $ids);

            foreach ($ids as $_id) {
                Item::find($_id)->delete();
            }
            $result['deleted_ids'] = $ids;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function getByCollection($collection) {
        $ItemModel = new Item();
        $products = $ItemModel->getItemByCollectionId($collection);
        if ($products) {
            $result['success'] = true;
            $result['data'] = $products;
            $result['response_time'] = microtime(true) - LARAVEL_START;

            return $this->responseSuccess($result);
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function search(Request $request) {
        $keyword = $request->get('keyword');
        if ($keyword) {

            $ids = $request->get('ids');
            $limit = $request->get('limit');
            if ($ids) {
                $ids = explode(',', $ids);
            }

            $ItemModel = new Item();
            $result = $ItemModel->listItem($ids, $limit, $keyword);
            if ($result) {
                //$result['response_time'] = microtime(true) - LARAVEL_START;
                return $this->responseSuccess($result);
            } else {
                $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
            }
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function review($seo_alias){
        $ItemModel = new Item();
        $result = $ItemModel->getItemBySlug($seo_alias);
        $user = User::checkToken();
        if ($result && $user){
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

    public function getBreadcrumb($res = [], $collection_id, $i = 0){
        $collection_id = $collection_id ? $collection_id : request()->get('collection');


        $collection = Collection::find($collection_id);
        if ($collection){
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


}
