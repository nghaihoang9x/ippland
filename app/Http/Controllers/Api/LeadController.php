<?php

namespace App\Http\Controllers\Api;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Lead;

use App\Models\Collection;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Validator;


class LeadController extends ApiController
{
    public function store(Request $request)
    {
        JWTAuth::parseToken();
        $token = JWTAuth::getToken();
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
        if ($user) {
            $data['user_id'] = $user->id;
        }

        $model = new Lead();

        $result = $model->saveLead($data);

        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "");
        }

    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        if (!$id) {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
        $lead = Lead::getById($id);
        if ($lead) {
            return $this->responseSuccess($lead);
        }

        $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function getBySlug($slug)
    {
        if (!$slug) {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }

        $LeadModel = new Lead();
        $result = $LeadModel->getLeadBySlug($slug);

        if ($result) {
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
    }

    public function list(Request $request)
    {
        $check = User::checkToken();
        $get = $request->all();
        if ($check) {
            $get['user_id'] = $check->_id;
        }
        $LeadModel = new Lead();
        $result = $LeadModel->listLead($get);
        if ($result) {

            return $this->responseSuccess($result);
        }

        $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function update(Request $request, $id)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }

        $LeadModel = new Lead();
        $result = $LeadModel->updateLead($data, $id);
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
        $LeadModel = new Lead();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $LeadModel->updateLead($data, $key);
        }

        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Category Not Found");
        }
    }

    public function delete($id)
    {
        $lead = Lead::deleteOnly($id);
        if ($lead) {
            $result['deleted_ids'] = $id;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function deleteMultiple(Request $request)
    {
        $ids = $request->get('ids');
        if ($ids) {
            $ids = explode(',', $ids);

            foreach ($ids as $_id) {
                Lead::find($_id)->delete();
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
        $LeadModel = new Lead();

        $leads = $LeadModel->getLeadByCollectionId($collection->_id, request());
        if ($leads) {
            $result['success'] = true;
            $result['data'] = $leads;
            $result['response_time'] = microtime(true) - LARAVEL_START;

            return $this->responseSuccess($result);
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function search(Request $request)
    {

        $get = $request->all();
        $LeadModel = new Lead();
        $result = $LeadModel->listLead($get);
        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
    }


    public function publish(Request $request)
    {
        $post = $request->all();
        $ids = isset($post['ids']) ? $post['ids'] : '';
        if ($ids) {
            $ids = explode(',', $ids);

            foreach ($ids as $_id) {
                $lead = Lead::find($_id);
                if ($lead) {
                    $lead->status = isset($lead->status) ? !$lead->status : true;
                    $lead->update();
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
                $lead = Lead::find($_id);
                if ($lead) {
                    $lead->available = isset($lead->available) ? !$lead->available : true;
                    $lead->update();
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
