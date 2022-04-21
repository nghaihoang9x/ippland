<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\Project;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;

class ProjectController extends ApiController
{
    public $model;

    public function __construct()
    {
        $this->model = New Project();
    }

    public function create(Request $request)
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
        $data['user_id'] = $user->id;
        $result = $this->model->save_project($data);
        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Not Found");
        }
    }

    public function getById($id)
    {
        $result = $this->model->getById($id);
        if ($result) {
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
    }


    public function getBySlug($slug)
    {
        if (!$slug) {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }

        $ProjectModel = new Project();
        $result = $ProjectModel->getProjectBySlug($slug);

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
        $result = $this->model->update_project($data, $id);
        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Not Found");
        }
    }

//    public function delete(Request $request) {
//        $ids = $request->get('ids');
//        if ($ids) {
//            $ids = explode(',', $ids);
//
//            foreach ($ids as $_id) {
//                $this->model::find($_id)->delete();
//            }
//            $result['deleted_ids'] = $ids;
//            $result['success'] = true;
//            $result['response_time'] = microtime(true) - LARAVEL_START;
//            return $this->responseSuccess($result);
//        }
//
//        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
//    }

    public function delete($id)
    {
        $project = $this->model->updateDeleted($id);
        if ($project) {

            return $this->responseSuccess($project);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");

    }
}
