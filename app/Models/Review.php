<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Carbon\Carbon;

class Review extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'reviews';

    public function user()
    {
        return $this->belongsTo('App\Models\User')->select(['avatar']);
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product')->select(['title', 'seo_alias']);
    }

    public function general($product_id){
        $count_review = Review::where('product_id', $product_id)->where('available', true)->count();
        $count_avg = Review::where('product_id', $product_id)->where('available', true)->avg('score');
        return [
            'count' => $count_review,
            'avg' => round($count_avg),
        ];
    }

    public function saveReview($data){

        $res = New Review();
        foreach ($data as $key => $val) {
            $res->{$key} = $val;
        }
        $res->save();
        if ($res->_id) {
            return $res->_id;
        }
    }

    public function updateReview($data, $id){
        $res = Review::find($id);
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }
            else
                $res->{$key} = $val;
        }
        return $res->update();
    }

    public static function listReview($request){
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $start = isset($request['start_date']) ? $request['start_date'] : '';
        $stop = isset($request['end_date']) ? $request['end_date'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        $type = isset($request['type']) ? $request['type'] : 'created_at';

        if ($ids) {
            $ids = explode(',', $ids);
        }

        $list = Review::where('deleted', '!=', 1);

        if ($start && $stop) {

            $sdt = Carbon::createFromFormat('Y-m-d', $start);
            $edt = Carbon::createFromFormat('Y-m-d', $stop);
            $list = $list->where(function ($query) use ($type, $sdt, $start){
                $query->where($type, '>', Carbon::create($sdt->year, $sdt->month, $sdt->day, 0, 0, 0))
                    ->orWhere($type, '>', $start.' 00:00:00');
            });

            $list = $list->where(function ($query) use ($type, $edt, $stop){
                $query->where($type, '<', Carbon::create($edt->year, $edt->month, $edt->day, 23, 59, 59))
                    ->orWhere($type, '<', $stop.' 23:59:59');
            });
        }

        $limit = $limit != '' ? $limit : env('PAGINATION');
        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }
        if ($keyword) {
            $list = $list->where('message', 'regexp', '/.*'.$keyword.'/i');
        }

        if($request){
            foreach ($request as $k => $v){
                if(!in_array($k, ['ids', 'limit', 'keyword', 'start_date', 'end_date', 'page', 'type', 'options', 'device_token', 'carts', 'query'])){
                    if($v == 'false' || $v == 'true')
                        $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                    $list = $list->where($k, $v);
                }
            }
        }

        $response = $list->orderBy($type, 'desc')->with('user')->with('product')->paginate(intval($limit));
        $products = $response->toArray();

        return $products;
    }
}