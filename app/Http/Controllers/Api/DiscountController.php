<?php

namespace App\Http\Controllers\Api;

use App\Models\Staff;
use Illuminate\Http\Request;
use App\Models\Discount;
use App\Helpers\Common;

class DiscountController extends ApiController
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
        $DiscountModel = new Discount();
        $result = $DiscountModel->saveDiscount($data);
        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Discount Not Found");
        }

    }

    public function list(Request $request)
    {
        $get = $request->all();
        $DiscountModel = new Discount();
        $result = $DiscountModel->listDiscount($get);

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
        $DiscountModel = new Discount();
        $result = $DiscountModel->updateDiscount($data, $id);
        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Discount Not Found");
        }
    }

    public function updateMultiple(Request $request)
    {
        $data = [];
        $post = $request->all();
        $DiscountModel = new Discount();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $DiscountModel->updateDiscount($data, $key);
        }

        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Discount Not Found");
        }
    }

    public function delete(Request $request) {
        $ids = $request->get('ids');
        $DiscountModel = new Discount();
        if ($ids) {
            $ids = explode(',', $ids);
            foreach ($ids as $_id) {
                $data['deleted'] = 1;
                $DiscountModel->updateDiscount($data, $_id);
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
        $get = $request->all();
        if ($keyword) {
            $DiscountModel = new Discount();
            $result = $DiscountModel->listDiscount($get);
            if ($result) {
                //$result['response_time'] = microtime(true) - LARAVEL_START;
                return $this->responseSuccess($result);
            } else {
                $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
            }
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function publish(Request $request) {
        $post = $request->all();
        $ids = isset($post['ids']) ? $post['ids'] : '';
        if ($ids) {
            $ids = explode(',', $ids);

            foreach ($ids as $_id) {
                $product = Discount::find($_id);
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
                $product = Discount::find($_id);
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

    public function addMultiple(Request $request){
        $data = [];
        $res = new Discount();
        $post = $request->all();
        $generator = isset($post['generator']) ? $post['generator'] : [];
        if(empty($generator)){
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Discount Not Found");
        }
        unset($post['generator']);
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }
        $rule = isset($generator['discount_rule']) ? $generator['discount_rule'] : 'auto';
        $number = isset($generator['discount_number']) ? intval($generator['discount_number']) : 0;
        $coupons = isset($generator['coupons']) ? $generator['coupons'] : [];
        $prefix = isset($generator['prefix_discount_code']) ? $generator['prefix_discount_code'] : '';
        $chart_number = isset($generator['chart_number']) ? intval($generator['chart_number']) : 4;

        if($rule == 'import') {
            foreach ($coupons as $coupon){
                $new_item = $data;
                $new_item['rule'] = 'auto';
                $new_item['title'] = strtoupper($prefix . $coupon);
                $new_item['seo_alias'] = $res->generateSlug($new_item['title'], 'seo_alias');
                Discount::forceCreate($new_item);
            }
        }else{
            for($i = 0; $i < $number; $i++){
                $new_item = $data;
                $rand_string = Common::random_string($chart_number);
                $new_item['rule'] = 'auto';
                $new_item['title'] = strtoupper($prefix . $rand_string);
                $new_item['seo_alias'] = $res->generateSlug($new_item['title'], 'seo_alias');
                Discount::forceCreate($new_item);
            }
        }
        $res['request'] = $request->all();
        $res['response_time'] = microtime(true) - LARAVEL_START;
        return $this->responseSuccess($res);
    }
}
