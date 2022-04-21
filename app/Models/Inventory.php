<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;
use Illuminate\Database\Eloquent\Builder;

class Inventory extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'inventories';

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    public function warehouse(){
        return $this->belongsTo('App\Models\Warehouse', 'warehouse_id', '_id');
    }

    public function variant(){
        return $this->belongsTo('App\Models\Variant', 'variant_id', '_id')->whereHas('product')->with('product');
    }

    protected static function boot()
    {
        parent::boot();
        self::saving(function ($model) {
            if(isset($model->inventory))
                $model->inventory = intval($model->transactions);
        });

    }

    public function saveInventory($data){
        $res = new Inventory();
        foreach ($data as $key => $val) {
            $res->{$key} = $val;
        }
        $res->save();
        return $res->_id;
    }

    public function updateInventory($data, $id){
        $res = Inventory::find($id);
        foreach ($data as $key => $val) {
            if(in_array($key, ['_id', 'id', 'variant_id', 'warehouse_id', 'quantity', 'updated_at', 'created_at', 'hold_quantity']))
                $res->{$key} = $val;
        }
        $status = $res->update();
        if($status){
            $quantity_by_variant = Inventory::where('variant_id', $res->variant_id)->sum('quantity');
            $variant = Variant::where('_id', $res->variant_id);
            if(isset($variant->quantity)){
                $variant->quantity = $quantity_by_variant;
                $variant->update();
            }
        }
        return $status;
    }

    public function listInventory($request) {
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $list = Inventory::where('deleted', '!=', 1);
        if ($keyword) {
            $list->whereHas('variant', function (Builder $query) use ($keyword){
                $query->whereHas('product', function (Builder $query2) use ($keyword){
                    $query2->where('title', 'regexp', '/.*'.$keyword.'/i');
                });
            });
//            $list->where('title', 'regexp', '/.*'.$keyword.'/i');
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

        $response = $list->orderBy('updated_at', 'desc')->whereHas('variant')->with('variant')->with('warehouse')->paginate(intval($limit));

        $inventories = $response->toArray();

        if($inventories['data']){
            foreach ($inventories['data'] as $key => $inventory){
                $inventories['data'][$key] = [
                    '_id' => $inventory['_id'],
                    'variant_id' => $inventory['variant_id'],
                    'warehouse_id' => $inventory['warehouse_id'],
                    'variant_title' => $inventory['variant']['title'],
                    'variant_image' => $inventory['variant']['image'],
                    'product_id' => $inventory['variant']['product']['_id'],
                    'product_title' => $inventory['variant']['product']['title'],
                    'product_images' => isset($inventory['variant']['product']['images']) ? $inventory['variant']['product']['images'] : [],
                    'sku' => $inventory['variant']['sku'],
                    'variant_quantity' => $inventory['variant']['quantity'],
                    'quantity' => $inventory['quantity'],
                    'warehouse' => $inventory['warehouse']['name'],
                ];
            }
        }
        return $inventories;
    }

    public function getInventoryByVariantId($variant_id){
        $res = [];
        $warehouse = Warehouse::where('deleted', '!=', 1)->get(['name'])->toArray();
        if($warehouse){
            foreach ($warehouse as $k => $item){
                $inventory = Inventory::where('variant_id', $variant_id)->where('warehouse_id', $item['_id'])->first();
                $res[$k]['id'] = $item['_id'];
                $res[$k]['quantity'] = isset($inventory->quantity) ? $inventory->quantity : 0;
            }
        }

        return $res;
    }
}