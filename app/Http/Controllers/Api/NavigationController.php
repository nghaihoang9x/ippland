<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Navigation;
use App\Models\NavigationItem;
use App\Http\Controllers\Api\ApiController;

class NavigationController extends ApiController
{
    public function store(Request $request)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }

        $NavigationModel = new Navigation();
        $result = $NavigationModel->saveNavigation($data);
        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Navigation Not Found");
        }

    }

    public function list(Request $request)
    {
        $NavigationModel = new Navigation();
        $ids = $request->get('ids');
        $limit = $request->get('limit');
        $parent = $request->get('parent') ? true : false;
        if ($ids) {
            $ids = explode(',', $ids);
        }
        $result = $NavigationModel->listNavigation($ids, $limit);

        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function getChildren(Request $request)
    {
        $NavigationModel = new Navigation();
        $ids = $request->get('ids');
        $limit = $request->get('limit');
        if ($ids) {
            $ids = explode(',', $ids);
        }
        $result = $NavigationModel->listNavigationChildren($ids, 1000);

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

        $NavigationModel = new Navigation();
        $result = $NavigationModel->getProductBySlug($slug);

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
        $NavigationModel = new Navigation();
        $result = $NavigationModel->updateNavigation($data, $id);
        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Navigation Not Found");
        }
    }

    public function updateMultiple(Request $request)
    {

        $data = [];
        $post = $request->all();
        $NavigationModel = new Navigation();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $NavigationModel->updateNavigation($data, $key);
        }

        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Navigation Not Found");
        }
    }

    public function delete(Request $request) {
        $ids = $request->get('ids');
        $NavigationModel = new Navigation();
        if ($ids) {
            $ids = explode(',', $ids);
            foreach ($ids as $_id) {
                $data['deleted'] = 1;
                $NavigationModel->updateNavigation($data, $_id);
            }
            $result['deleted_ids'] = $ids;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }


    public function listNavigation()
    {
        $Navigations = Navigation::getAllNavigation();
        if ($Navigations) return $this->responseSuccess($Navigations);

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function getMenu(Request $request)
    {
        $menu_id = $request->get('menu_id');

        if ($menu_id) {
            $NavigationModel = new Navigation();
            $menu = $NavigationModel->getByMenuId($menu_id);
            if ($menu) return $this->responseSuccess($menu);
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function getTopMenu()
    {

        $NavigationModel = new Navigation();
        $menu = $NavigationModel->getDefaultMenu();
        if ($menu) return $this->responseSuccess($menu);

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

            $NavigationModel = new Navigation();
            $result = $NavigationModel->listNavigation($ids, $limit, $keyword);
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
