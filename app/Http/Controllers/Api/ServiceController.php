<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Http\Controllers\Api\ApiController;

class ServiceController extends ApiController
{
    public function store(Request $request)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }
        $ServiceModel = new Service();
        $result = $ServiceModel->saveService($data);
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
        $order['options']['email'] = 'hoamt@site4com.net';
        $user = User::where('email', $order['options']['email'])->first();
        dd($user);
        $ServiceModel = new Service();
        $ids = $request->get('ids');

        $limit = $request->get('limit');
        $parent = $request->get('parent') ? true : false;
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $result = $ServiceModel->listService($ids, $limit);

        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function getChildren(Request $request)
    {
        $ServiceModel = new Service();
        $ids = $request->get('ids');
        $limit = $request->get('limit');
        if ($ids) {
            $ids = explode(',', $ids);
        }
        $result = $ServiceModel->listServiceChildren($ids, $limit);

        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function getBySlug($slug) {
        if (!$slug) {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }

        $ServiceModel = new Service();
        $result = $ServiceModel->getServiceBySlug($slug);

        if ($result) {
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
        $ServiceModel = new Service();
        $result = $ServiceModel->updateService($data, $id);
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
        $ServiceModel = new Service();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $ServiceModel->updateService($data, $key);
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
        $ServiceModel = new Service();
        if ($ids) {
            $ids = explode(',', $ids);
            foreach ($ids as $_id) {
                $data['deleted'] = 1;
                $ServiceModel->updateService($data, $_id);
            }
            $result['deleted_ids'] = $ids;
            $result['success'] = true;
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

            $ServiceModel = new Service();
            $result = $ServiceModel->listService($ids, $limit, $keyword);
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
