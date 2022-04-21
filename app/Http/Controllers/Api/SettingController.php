<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends ApiController
{
    public function store(Request $request)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }
        $SettingModel = new Setting();
        $result = $SettingModel->saveSetting($data);
        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Setting Not Found");
        }

    }

    public function list(Request $request)
    {
        $SettingModel = new Setting();
        $ids = $request->get('ids');
        $type = $request->get('type');

        $limit = $request->get('limit');
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $result = $SettingModel->listSetting($ids, $limit, $type);

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
        $SettingModel = new Setting();
        $result = $SettingModel->updateSetting($data, $id);
        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Setting Not Found");
        }
    }

    public function updateMultiple(Request $request)
    {
        $data = [];
        $post = $request->all();
        $SettingModel = new Setting();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $SettingModel->updateSetting($data, $key);
        }

        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Setting Not Found");
        }
    }

    public function homepage(Request $request) {
        return $this->responseSuccess(Setting::homepage($request));
    }

    public function general() {
        $settingModel = new Setting();
        return $this->responseSuccess($settingModel->general());
    }

    public function delete(Request $request) {
        $ids = $request->get('ids');
        $SettingModel = new Setting();
        if ($ids) {
            $ids = explode(',', $ids);
            foreach ($ids as $_id) {
                $data['deleted'] = 1;
                $SettingModel->updateSetting($data, $_id);
            }
            $result['deleted_ids'] = $ids;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }
}
