<?php

namespace App\Http\Controllers\Api;

use App\Models\Staff;
use Illuminate\Http\Request;
use App\Models\Collection;
use App\Http\Controllers\Api\ApiController;
use App\Helpers\Common;

class CollectionController extends ApiController
{
    public function store(Request $request)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }
        $staff = Staff::checkToken();
        if($staff && $staff != 'expired' && $staff->role == 'admin'){
            $data['status'] = true;
        }else{
            $data['status'] = false;
        }
        $CollectionModel = new Collection();
        $result = $CollectionModel->saveCollection($data);
        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Collection Not Found");
        }

    }

    public function list(Request $request)
    {
        $get = $request->all();
        $CollectionModel = new Collection();

        $result = $CollectionModel->listCollection($get);

        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function getChildren(Request $request)
    {
        $CollectionModel = new Collection();
        $ids = $request->get('ids');
        $limit = $request->get('limit');
        if ($ids) {
            $ids = explode(',', $ids);
        }
        $result = $CollectionModel->listCollectionChildren($ids, 1000);

        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function getBySlug(Request $request, $slug) {
        if (!$slug) {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }

        $get = $request->all();
        $CollectionModel = new Collection();
        $result = $CollectionModel->getProductBySlug($slug, $get, 'desc');

        if ($result) {
            $result['request'] = $request->all();
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
        $CollectionModel = new Collection();
        $result = $CollectionModel->updateCollection($data, $id);
        if ($result) {
            Common::updateSeoAlias($data['seo_alias'], $id, 'collection');
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Collection Not Found");
        }
    }

    public function updateMultiple(Request $request)
    {
        $data = [];
        $post = $request->all();
        $CollectionModel = new Collection();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $CollectionModel->updateCollection($data, $key);
        }

        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Collection Not Found");
        }
    }

    public function delete(Request $request) {
        $ids = $request->get('ids');
        $CollectionModel = new Collection();
        if ($ids) {
            $ids = explode(',', $ids);
            foreach ($ids as $_id) {
                $data['deleted'] = 1;
                $CollectionModel->updateCollection($data, $_id);
            }
            $result['deleted_ids'] = $ids;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }


    public function listCollection()
    {
        $collections = Collection::getAllCollection();
        if ($collections) return $this->responseSuccess($collections);

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function loadSpecs($collection_id) {
        $result = Collection::loadSpecsFilter($collection_id);

        return $this->responseSuccess($result);
    }

    public function search(Request $request) {
        $get = $request->all();

        $CollectionModel = new Collection();
        $result = $CollectionModel->listCollection($get);
        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
    }

    // new function for box

    public function getProductsBySlug($slug) {
        $CollectionModel = new Collection();
        $result = $CollectionModel->getProductBySlug($slug);

        if ($result) {
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
    }

    public function publish(Request $request) {
        $post = $request->all();
        $ids = isset($post['ids']) ? $post['ids'] : '';
        if ($ids) {
            $ids = explode(',', $ids);

            foreach ($ids as $_id) {
                $product = Collection::find($_id);
                if($product) {
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

    public function available(Request $request) {
        $post = $request->all();
        $ids = isset($post['ids']) ? $post['ids'] : '';
        if ($ids) {
            $ids = explode(',', $ids);

            foreach ($ids as $_id) {
                $product = Collection::find($_id);
                if($product) {
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
