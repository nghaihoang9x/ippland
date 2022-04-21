<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleController extends ApiController
{

    protected $guard = 'staff';

    function __construct()
    {
        \Config::set('jwt.user', Staff::class);
        \Config::set('auth.providers', ['users' => [
            'driver' => 'eloquent',
            'model' => Staff::class,
        ]]);
    }

    public function store(Request $request)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }
        $RoleModel = new Role();
        $result = $RoleModel->saveRole($data);
        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Role Not Found");
        }

    }

    public function list(Request $request)
    {
        $RoleModel = new Role();
        $ids = $request->get('ids');

        $limit = $request->get('limit');
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $result = $RoleModel->listRole($ids, $limit);

        if ($result) {
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
        $RoleModel = new Role();
        $result = $RoleModel->updateRole($data, $id);
        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Role Not Found");
        }
    }

    public function updateMultiple(Request $request)
    {
        $data = [];
        $post = $request->all();
        $RoleModel = new Role();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $RoleModel->updateRole($data, $key);
        }

        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Role Not Found");
        }
    }

    public function delete(Request $request) {
        $ids = $request->get('ids');
        $RoleModel = new Role();
        if ($ids) {
            $ids = explode(',', $ids);
            foreach ($ids as $_id) {
                $data['deleted'] = 1;
                $RoleModel->updateRole($data, $_id);
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

            $RoleModel = new Role();
            $result = $RoleModel->listRole($ids, $limit, $keyword);
            if ($result) {
                //$result['response_time'] = microtime(true) - LARAVEL_START;
                return $this->responseSuccess($result);
            } else {
                $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
            }
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function getPermissions(){
        $staff = Staff::checkToken();
        if ($staff == false || $staff == 'expired'){
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error");
        }

        $role = Role::where('slug', '=', $staff->role)->first();

        return response()->json(compact('staff', 'role'));
    }

    public function checkPermission($permissions){
        $staff = Staff::checkToken();
        if ($staff == false || $staff == 'expired'){
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error");
        }

        $role = $staff->hasAccess(['blog.create']);
        return response()->json($role);
    }
}
