<?php

namespace App\Http\Controllers\Api;

use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Auth;

class StaffController extends ApiController
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
        $credentials = request()->all();

        if ( $user = Staff::where('email', '=', $credentials['email'])->where('deleted', '!=', 1)->first() )
        {
            return response()->json(['error' => 'Email đã tồn tại'], 200);
        }
        else{
            $user = Staff::createUser($credentials);
            if ($user)
                return response()->json(['error' => false, 'user' => $user, 'code' => 200], 200);
            return response()->json(['error' => 'could_not_create_user'], 500);
        }
    }

    public function list(Request $request)
    {
        $get = $request->all();
        $StaffModel = new Staff();

        $result = $StaffModel->listStaff($get);

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
        $StaffModel = new Staff();
        $result = $StaffModel->updateStaff($data, $id);
        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Staff Not Found");
        }
    }

    public function updateMultiple(Request $request)
    {
        $data = [];
        $post = $request->all();
        $StaffModel = new Staff();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $StaffModel->updateStaff($data, $key);
        }

        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Staff Not Found");
        }
    }

    public function delete(Request $request) {
        $ids = $request->get('ids');
        $StaffModel = new Staff();
        if ($ids) {
            $ids = explode(',', $ids);
            foreach ($ids as $_id) {
                $data['deleted'] = 1;
                $StaffModel->updateStaff($data, $_id);
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

        $StaffModel = new Staff();
        $result = $StaffModel->listStaff($get);
        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
    }

    public function login(Request $request)
    {
        \Config::set( 'jwt.user', Staff::class );
        \Config::set( 'auth.providers.users.model', Staff::class );
        $credentials = $request->only('email', 'password');
        if ( $staff = Staff::where('email', '=', $credentials['email'])->where('deleted', '!=', 1)->first() )
        {
            if ( !Hash::check($credentials['password'], $staff->password ) )
                $result = [
                    'success' => false,
                    'data' => []
                ];
            else
            {
                $token = JWTAuth::fromUser($staff);
                $role = Role::where('slug', '=', $staff->role)->first();
                $result = [
                    'success' => true,
                    'data' => compact('token', 'staff', 'role')
                ];
            }
        }
        else
            $result = [
                'success' => false,
                'data' => []
            ];

        return $this->responseSuccess($result);
    }

    public function profile(Request $request)
    {
        $staff = Staff::checkToken();
        if($staff && $staff != 'expired') {

            $get = $request->all();
            $get['ids'] = $staff->_id;
            $StaffModel = new Staff();

            $result = $StaffModel->listStaff($get);

            if ($result) {
                return $this->responseSuccess($result);
            }
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

}
