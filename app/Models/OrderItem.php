<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class OrderItem extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'order_items';

    public function order()
    {
        return $this->belongsTo('App\Models\Order', 'order_id', '_id');
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'product_id', '_id');
    }

    public function variant()
    {
        return Variant::where(function ($query){
            $query->orWhere('_id', $this->variant_id);
            $query->orWhere('variant_id', $this->variant_id);
            $query->orWhere('id', $this->variant_id);
        })->first();
    }

    public function validateSave(){
        return true;
    }

    protected static function boot()
    {
        parent::boot();
        self::saving(function ($model) {

        });
    }

    public function listOrders($request) {
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        $type = isset($request['type']) ? $request['type'] : 'created_at';

        $query = isset($request['query']) ? $request['query'] : 'search';

        if ($ids) {
            $ids = explode(',', $ids);
        }

        if(is_array($ids) && count($ids)){
            $list = Order::where('deleted', '!=', 1);
        }
        elseif($type == 'draft'){
            $list = Order::where('deleted', '!=', 1)->where('type', '=', 'draft');
            $type = 'created_at';
        }else{
            $list = Order::where('deleted', '!=', 1)->where('type', '!=', 'draft');
        }

        if(!$ids && $query == 'list') {
            $list = $list->select(['payment_method', 'payment_select', 'user_id', 'payment_cart', 'payment_bank', 'payment_status', 'shipping_method', 'shipping_status', 'shipping_shipper', 'shipping_tracking_code', 'shipping_price', 'title', 'order_status', 'subtotal_price', 'total_price', 'total_discounts', 'discounts_code', 'total_shipping', 'customer', 'type', 'box_token', 'updated_at', 'created_at', 'id', 'tags']);
        }

        if ($keyword) {
            $list->orWhere('title', 'regexp', '/.*'.$keyword.'/i');
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

        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }
        $response = $list->orderBy($type, 'desc')->paginate(intval($limit));

        $res = $response->toArray();

        return $res;

    }

    public function updateOrder() {

    }

}