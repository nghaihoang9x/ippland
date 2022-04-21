<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Wishlist extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'wishlist';

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    public function product(){
        return $this->hasOne('App\Models\Product');
    }

    public function saveWishlist($data){
        $res = new Wishlist();
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->{$key} = $res->generateSlug($val, 'seo_alias');
            }else if ($key == 'title'){
                $res->{$key} = $val;
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }else
                $res->{$key} = $val;
        }
        $res->save();
        return $res->_id;
    }

    public function updateWishlist($data, $id){
        $res = Wishlist::find($id);
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }
            else
                $res->{$key} = $val;
        }
        return $res->update();
    }

    public function listWishlist($request) {
        $reviewModel = new Review();
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $list = Wishlist::where('deleted', '!=', 1);
        if ($keyword) {
            $list->where('title', 'regexp', '/.*'.$keyword.'/i');
        }

        if (is_array($ids) && count($ids)) {
            $list->whereIn('_id', $ids);
        }

        if($request){
            foreach ($request as $k => $v){
                if(!in_array($k, ['ids', 'limit', 'keyword', 'start_date', 'end_date', 'page', 'type', 'api_token'])){
                    if($v == 'false' || $v == 'true')
                        $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                    $list->where(function ($query) use ($k, $v){
                        $query->orWhere($k, $v)
                            ->orWhere(str_replace('_', '.', $k), $v);
                    });
                }
            }
        }

        $limit = $limit != '' ? $limit : env('PAGINATION');

        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));
        $res = $response->toArray();
        if (count($res['data'])) {
            foreach ($res['data'] as $key => $item) {
                $product = Product::where('_id', $item['product_id'])->first();
                $variant = Variant::where('_id', $item['variant_id'])->first();
                if($product && $variant) {
                    $res['data'][$key] = $product;
                    $res['data'][$key]['variants'] = $variant;
                    $res['data'][$key]['review'] = $reviewModel->general($product->_id);
                    $res['data'][$key]['wishlist_id'] = $item['_id'];
                }else{
                    unset($res['data'][$key]);
                }
            }
            $res['data'] = array_values($res['data']);
        }
        return $res;
    }

}