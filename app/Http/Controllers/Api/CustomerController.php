<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Customer;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;

class CustomerController extends ApiController
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
        $data['user_id'] = $user->_id;
        $CustomerModel = new Customer();
        $result = $CustomerModel->saveCustomer($data);
        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Customer Not Found");
        }

    }

    public function list(Request $request)
    {
        $get = $request->all();
        $CustomerModel = new Customer();
        $result = $CustomerModel->listCustomer($get);
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
        $CustomerModel = new Customer();
        $result = $CustomerModel->updateCustomer($data, $id);
        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            $res['success'] = true;
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Customer Not Found");
        }
    }

    public function updateMultiple(Request $request)
    {
        $data = [];
        $post = $request->all();
        $CustomerModel = new Customer();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $CustomerModel->updateCustomer($data, $key);
        }

        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Customer Not Found");
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $customer = Customer::deleteOnly($id);
        if ($customer) {

            return $this->responseSuccess($customer);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function deleteMultiple(Request $request)
    {
        $ids = $request->get('ids');
        $CustomerModel = new Customer();
        if ($ids) {
            $ids = explode(',', $ids);
            foreach ($ids as $_id) {
                $data['deleted'] = 1;
                $CustomerModel->updateCustomer($data, $_id);
            }
            $result['deleted_ids'] = $ids;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function search(Request $request)
    {
        $keyword = $request->get('keyword');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $get = $request->all();
        if ($keyword || $start_date || $end_date) {

            $OrderModel = new Customer();
            $result = $OrderModel->listCustomer($get);
//            if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            if ($start_date && $end_date) {
                $res['data'] = $result;
                $res['success'] = true;
                return $this->responseSuccess($res);
            }
            return $this->responseSuccess($result);
//            } else {
//                $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
//            }
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function search1(Request $request)
    {
        $keyword = $request->get('keyword');
        if ($keyword) {
            $ids = $request->get('ids');
            $limit = $request->get('limit');
            if ($ids) {
                $ids = explode(',', $ids);
            }

            $CustomerModel = new Customer();
            $result = $CustomerModel->listCustomer($ids, $limit, $keyword);
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
