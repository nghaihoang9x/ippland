<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Discount extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'discounts';

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    public function saveDiscount($data){
        $res = new Discount();
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->{$key} = $res->generateSlug($val, 'seo_alias');
            }else if ($key == 'title'){
                $res->{$key} = $val;
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }
            else $res->{$key} = $val;
        }
        $res->save();
        return $res->_id;
    }

    public function updateDiscount($data, $id){
        $res = Discount::find($id);
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }
            else $res->{$key} = $val;
        }
        return $res->update();
    }

    public function listDiscount($request) {
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';

        $list = Discount::where('deleted', '!=', 1);

        if ($keyword) {
            $list->where('title', 'regexp', '/.*'.$keyword.'/i');
        }

        $limit = $limit != '' ? $limit : env('PAGINATION');
        if ($ids) {
            $ids = explode(',', $ids);
        }
        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }
        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));

        return $response->toArray();
    }

    public function listDiscountByIds($ids) {
        if (count($ids)) {
            $res = [];
            if (is_array($ids) && count($ids)) {
                $categories = Discount::whereIn('id', $ids)->get();
                $i = 0;
                foreach ($categories as $_Discount) {
                    $res[$i]['label'] = $_Discount->title;
                    $res[$i]['value'] = $_Discount->_id;
                    $i++;
                }
            }
            return $res;
        }
    }
}