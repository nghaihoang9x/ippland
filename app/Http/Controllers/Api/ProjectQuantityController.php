<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\ProjectType;

class ProjectQuantityController extends ApiController
{
    public $model;
    public function __construct()
    {
        $this->model = New ProjectType();
    }
    public function create(Request $request)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }
        $result = $this->model->save($data);
        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Not Found");
        }
    }

    public function getById($type_id) {
        $result = $this->model->getById($type_id);
        if ($result) {
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
    }

    public function list(Request $request)
    {
        $get = $request->all();
        $result = $this->model->list($get);
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
        $result = $this->model->update($data, $id);
        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Not Found");
        }
    }

    public function delete(Request $request) {
        $ids = $request->get('ids');
        if ($ids) {
            $ids = explode(',', $ids);

            foreach ($ids as $_id) {
                $this->model::find($_id)->delete();
            }
            $result['deleted_ids'] = $ids;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }
}
