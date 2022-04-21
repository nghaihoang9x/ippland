<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Common;
use App\Models\Staff;
use Illuminate\Http\Request;
use App\Models\Page;
use App\Http\Controllers\Api\ApiController;

class PageController extends ApiController
{
    public function store(Request $request)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }
        $staff = Staff::checkToken();
        if($staff && $staff != 'expired' && $staff->role == 'admin'){
            $data['status'] = true;
        }else{
            $data['status'] = false;
        }
        $PageModel = new Page();
        $result = $PageModel->savePage($data);
        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Page Not Found");
        }

    }

    public function list(Request $request)
    {
        $get = $request->all();
        $PageModel = new Page();

        $result = $PageModel->listPage($get);

        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function getChildren(Request $request)
    {
        $PageModel = new Page();
        $ids = $request->get('ids');
        $limit = $request->get('limit');
        if ($ids) {
            $ids = explode(',', $ids);
        }
        $result = $PageModel->listPageChildren($ids, $limit);

        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function getByAlias($slug) {
        if (!$slug) {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }

        $PageModel = new Page();
        $result = $PageModel->getPageByAlias($slug);

        if ($result) {
            return $this->responseSuccess($result);
        } else {
            return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
    }

    public function update(Request $request, $id)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }
        $PageModel = new Page();
        $result = $PageModel->updatePage($data, $id);

        if ($result) {
            Common::updateSeoAlias($data['seo_alias'], $id, 'page');
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Page Not Found");
        }
    }

    public function updateMultiple(Request $request)
    {
        $data = [];
        $post = $request->all();
        $PageModel = new Page();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $PageModel->updatePage($data, $key);
        }

        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Page Not Found");
        }
    }

    public function delete(Request $request) {
        $ids = $request->get('ids');
        $PageModel = new Page();
        if ($ids) {
            $ids = explode(',', $ids);
            foreach ($ids as $_id) {
                $data['deleted'] = 1;
                $PageModel->updatePage($data, $_id);
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

        $PageModel = new Page();
        $result = $PageModel->listPage($get);
        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }

    }

    public function publish(Request $request) {
        $post = $request->all();
        $ids = isset($post['ids']) ? $post['ids'] : '';
        if ($ids) {
            $ids = explode(',', $ids);

            foreach ($ids as $_id) {
                $product = Page::find($_id);
                if($product) {
                    $product->status = isset($product->status) ? !$product->status : true;
                    $product->update();
                }
            }
            $result['hide_ids'] = $ids;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function available(Request $request) {
        $post = $request->all();
        $ids = isset($post['ids']) ? $post['ids'] : '';
        if ($ids) {
            $ids = explode(',', $ids);

            foreach ($ids as $_id) {
                $product = Page::find($_id);
                if($product) {
                    $product->available = isset($product->available) ? !$product->available : true;
                    $product->update();
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
