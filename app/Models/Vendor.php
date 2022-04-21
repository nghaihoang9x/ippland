<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Vendor extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'vendors';
    //protected $fillable = [];

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    public function saveVendor($data){
        $res = new Vendor();
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

    public function updateVendor($data, $id){
        $res = Vendor::find($id);
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }
            else $res->{$key} = $val;
        }


        return $res->update();
    }

    public function listVendor($request) {

        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $list = Vendor::where('deleted', '!=', 1);
        if ($keyword) {
            $list->where('title', 'regexp', '/.*'.$keyword.'/i');
        }

        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }
        if($request){
            unset($request['options']);
            unset($request['device_token']);
            unset($request['carts']);
            foreach ($request as $k => $v){
                if(!in_array($k, ['ids', 'limit', 'keyword', 'start_date', 'end_date', 'page', 'type', 'options', 'device_token', 'carts', 'query', 'discounts_code', 'plan_id', 'work_shift', 'buyer_type', 'api_token'])){
                    if($v == 'false' || $v == 'true')
                        $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                    $list->where($k, $v);
                }
            }
        }
        $response = $list->orderBy('created_at', 'desc')->paginate(intval($limit));

        return $response->toArray();

        /*if (is_array($ids) && count($ids)) {
            return Vendor::whereIn('_id', $ids)->where('deleted', '!=', 1)->get();
        }
        return Vendor::where('deleted', '!=', 1)->get();*/
    }

    public function listVendorByCollection($id) {
        $vendors = CollectionVendor::where('collection_id', $id)->get();

        $response = [];
        if (!count($vendors)) return [];
        foreach ($vendors as $_item) {
            $detail = Vendor::where('_id', $_item->vendor_id)->first();

            if (isset($response[$_item->vendor_id])) {
                $response[$_item->vendor_id]['total_product'] += 1;
            } else {
                $response[$_item->vendor_id]['id'] = $detail->_id;
                $response[$_item->vendor_id]['title'] = $detail->title;
                $response[$_item->vendor_id]['seo_alias'] = $detail->seo_alias;
                $response[$_item->vendor_id]['total_product'] = 1;
                $response[$_item->vendor_id]['images'] = $detail->images;
            }
        }
        return $response;
    }

    public function listVendorById($id, $list = false) {
        if ($list) {
            return $id;
        }
        $res = [];
        if ($id) {
            /*$vendor = Vendor::find($id);
            $res['label'] = $vendor->title;
            $res['value'] = $vendor->_id;
            $res['seo_alias'] = $vendor->seo_alias;
            $res['images'] = $vendor->images;*/
        }
        return $res;
    }


    public function getProductBySlug($slug) {
        $detail = Vendor::where('seo_alias', $slug)->first();
        $products = Product::where('deleted', '!=', 1)->where('vendor', $detail->_id)->get();
        return ['detail' => $detail, 'products' => $products];
    }

    public function listBrandById($brand_id, $field = ['id', 'title']){
        $vendor = Vendor::where('_id', $brand_id)->first($field);
        return $vendor;
    }
}