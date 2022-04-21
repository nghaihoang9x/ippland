<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Variant;
use App\Http\Controllers\Api\ApiController;

class VariantController extends ApiController
{
    public function store(Request $request)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }
        $ServiceModel = new Variant();
        $result = $ServiceModel->saveVariant($data);
        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Service Not Found");
        }

    }

    public function list(Request $request)
    {
        $get = $request->all();
        $ServiceModel = new Variant();
        $result = $ServiceModel->listVariant($get);

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
        $ServiceModel = new Variant();
        $result = $ServiceModel->updateVariant($data, $id);
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
        $ServiceModel = new Variant();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $ServiceModel->updateVariant($data, $key);
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
        $ServiceModel = new Variant();
        if ($ids) {
            $ids = explode(',', $ids);
            foreach ($ids as $_id) {
                $data['deleted'] = 1;
                $ServiceModel->updateVariant($data, $_id);
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

            $ServiceModel = new Variant();
            $result = $ServiceModel->listVariant($get);
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
