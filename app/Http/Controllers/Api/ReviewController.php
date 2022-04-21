<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Http\Controllers\Api\ApiController;

class ReviewController extends ApiController
{
    public function store(Request $request)
    {
        $user = User::checkToken();
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }
        $data['user_id'] = $user && $user != 'expired' ? $user->_id : '';
        $reviewModel = new Review();
        $result = $reviewModel->saveReview($data);
        if ($result) {
            $res['_id'] = $result;
            $res['request'] = $request->all();
            $res['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Category Not Found");
        }

    }

    public function show($id)
    {
        $reviews = Review::where('product_id', '=', $id)->with('user')->get();
        return $reviews;
    }

    public function list(Request $request)
    {
        $get = $request->all();
        $reviewModel = new Review();
        $result = $reviewModel->listReview($get);
        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }

    }

    public function getByProductId(Request $request, $product_id){
        $get = $request->all();
        $get['product_id'] = $product_id;
        $get['available'] = true;
        $reviewModel = new Review();
        $reviews = $reviewModel->listReview($get);
        $res = $reviews;
        $res['success'] = true;
        return $this->responseSuccess($res);
    }

    public function review($seo_alias){
        $result = Product::where('seo_alias' , $seo_alias)->first();
        $user = User::checkToken();
        if ($result && $user){
            $review = new Review;
            $review->title = request()->get('title');
            $review->comment = request()->get('comment');
            $review->score = request()->get('score');
            $review->product_id = (string)$result->_id;
            $review->user_id = (string)$user->_id;
            $review->save();

            $result->score = $result->getCaculateProductScore();
            $result->save();
            return $this->responseSuccess($review);
        }

        return $this->sendError("Not Found 1", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found 2");
    }

    public function update(Request $request, $id)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }

        $ReviewModel = new Review();
        $result = $ReviewModel->updateReview($data, $id);
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
        $ReviewModel = new Review();
        foreach ($post as $key => $value) {
            foreach ($value as $k => $v) {
                $data[$k] = $v;
            }
            $result = $ReviewModel->updateReview($data, $key);
        }

        if ($result) {
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['request'] = $request->all();
            return $this->responseSuccess($res);
        } else {
            $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Category Not Found");
        }
    }

    public function delete(Request $request) {
        $ids = $request->get('ids');
        $ReviewModel = new Review();
        if ($ids) {
            $ids = explode(',', $ids);

            foreach ($ids as $_id) {
                $data['deleted'] = 1;
                $ReviewModel->updateReview($data, $_id);
            }
            $result['deleted_ids'] = $ids;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function publish(Request $request) {
        $post = $request->all();
        $ids = isset($post['ids']) ? $post['ids'] : '';
        if ($ids) {
            $ids = explode(',', $ids);

            foreach ($ids as $_id) {
                $review = Review::find($_id);
                if($review) {
                    $review->available = !$review->available;
                    $review->update();
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
