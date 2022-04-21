<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Variant extends Eloquent {
    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'variants';

    protected $dates = ['deleted_at'];
    protected $fillable = [
        'id', 'product_id', 'title', 'image', 'sku', 'regular_price', 'compare_price', 'quantity', 'barcode',
        'weight', 'length', 'height', 'width', 'option1', 'option2', 'option3', 'option4', 'hold_quantity',
        'title_en', 'title_cn',
        'option1_en', 'option2_en', 'option3_en', 'option4_en',
        'option1_cn', 'option2_cn', 'option3_cn', 'option4_cn'
    ];

    public static $ATTR = [];

    public function product(){
        return $this->belongsTo('App\Models\Product', 'product_id', '_id');
    }

    public function inventory(){
        return $this->hasMany('App\Models\Inventory', 'variant_id', '_id')->with('warehouse');
    }

    protected static function boot()
    {
        parent::boot();
        self::saving(function ($model) {
            $model->regular_price = (int)$model->regular_price;
            $model->compare_price = $model->compare_price ? (int)$model->compare_price : '';
            $model->quantity = (int)$model->quantity;
            $model->featured_sort = (int)$model->featured_sort;
            if(isset($model->hold_quantity))
                $model->hold_quantity = (int)$model->hold_quantity;
        });
    }

    public function validateSave(){
        return true;
    }

    public function saveVariant($data){
        $res = new Variant();
        foreach ($data as $key => $val) {
            $res->{$key} = $val;
        }
        $res->save();
        return $res->_id;
    }

    public function updateVariant($data, $id){
        $res = Variant::find($id);
        foreach ($data as $key => $val) {
            $res->{$key} = $val;
        }
        return $res->update();
    }

    public function listVariant($request) {
        $vendorModel = new Vendor();
        $collectionModel = new Collection();
        $inventoryModel = new Inventory();
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        $collection = isset($request['collection']) ? $request['collection'] : '';
        $price = isset($request['price']) ? $request['price'] : '';
        $volume = isset($request['volume']) ? $request['volume'] : '';
        $brand = isset($request['brand']) ? $request['brand'] : '';
        $intensity = isset($request['intensity']) ? $request['intensity'] : '';
        $available = isset($request['available']) ? $request['available'] : '';
        $featured = isset($request['featured']) ? $request['featured'] : '';
        $status = isset($request['status']) ? $request['status'] : '';
        $homepage = isset($request['homepage']) ? $request['homepage'] : '';
        $locale = isset($request['locale']) ? '_' . $request['locale'] : '';
        $is_frontend = isset($request['is_frontend']) ? $request['is_frontend'] : false;
        if (strtolower($locale) === '_vi' || strtolower($locale) === '_vn'){
            $locale = '';
        }
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $list = Variant::where('deleted', '!=', 1);
        $list->whereHas('product');
        if($collection && $collection != 'all'){

            $list->whereHas('product', function (Builder $query) use ($collection){
                $query->where('collections', '=', $collection);
            });
        }
        if($price){
            $price_ranger = explode(',', $price);
            if(isset($price_ranger[0]))
                $list->where('regular_price', '>=', intval($price_ranger[0]));
            if(isset($price_ranger[1]))
                $list->where('regular_price', '<=', intval($price_ranger[1]));
        }
        if($available){
            $list->whereHas('product', function (Builder $query) use ($brand){
                $query->where('available', true);
            });
        }
        if ($brand) {
            $list->whereHas('product', function (Builder $query) use ($brand){
                $query->where('brand', '=', $brand);
            });
        }
        if ($intensity) {
            $list->whereHas('product', function (Builder $query) use ($intensity){
                $query->where('intensity', 'regexp', '/.*'.$intensity.'/i');
            });
        }
        if ($keyword) {
            $list->where(function ($q) use ($keyword, $locale){
                $q->whereHas('product', function (Builder $query) use ($keyword, $locale){
                    $query->orWhere('title' . $locale, 'regexp', '/.*'.$keyword.'/i');
                });
                $q->orWhere('title' . $locale, 'regexp', '/.*'.$keyword.'/i');
            });

//            $list->orWhere('title', 'regexp', '/.*'.$keyword.'/i');
        }

        if($featured){
            $list->where(function ($q){
                $q->whereHas('product', function (Builder $query){
                    $query->orWhere('featured', true);
                });
                $q->orWhere('featured', true);
            });
        }

        if($status){
            $list->whereHas('product', function (Builder $query){
                $query->where('status', '!=', false);
            });
        }

        if ($volume) {
            $list->where(function ($q) use ($volume, $locale){
                $q->orWhere('option1' . $locale, 'like', "$volume")
                    ->orWhere('option2' . $locale, 'like', "$volume")
                    ->orWhere('option3' . $locale, 'like', "$volume")
                    ->orWhere('option4' . $locale, 'like', "$volume");
            });
        }

        if (is_array($ids) && count($ids)) {
            $list->whereIn('_id', $ids);
        }

        if($request){

            foreach ($request as $k => $v){
                if(!in_array($k, ['ids', 'limit', 'keyword', 'start_date', 'end_date',
                    'page', 'type', 'collection', 'price', 'volume', 'sort', 'brand', 'intensity',
                    'api_token', 'available', 'featured', 'status', 'homepage', 'locale'])){
                    if ($is_frontend) {
                        continue;
                    }
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

        if($homepage){
            $list = $list->orderBy('featured_sort', 'asc');
        }

        $response = $list->orderBy('product_id', 'desc')->with('inventory')->paginate(intval($limit));

        $res = $response->toArray();
        if (count($res['data'])) {
            foreach ($res['data'] as $key => $item) {
                $product = Product::where('_id', $item['product_id'])->first();
                if($product) {
                    $item['inventory'] = $inventoryModel->getInventoryByVariantId($item['_id']);
                    $res['data'][$key] = $product;
                    $res['data'][$key]['collections'] = isset($product['collections']) ? $collectionModel->listCollectionByIds($product['collections']) : [];
                    $res['data'][$key]['brand'] = isset($product['brand']) ? $vendorModel->listBrandById($product['brand']) : [];
                    $res['data'][$key]['variants'] = [0 => $item];
                    $res['data'][$key]['_id'] = $item['_id'];
                    $res['data'][$key]['id'] = $item['_id'];
                }else{
                    unset($res['data'][$key]);
                }
            }
            $res['data'] = array_values($res['data']);
        }
        return $res;
    }

    public static function getVariantByProductId($product_id){
        $inventoryModel = new Inventory();
        $res = [];
        $variants = Variant::where('product_id', $product_id)->where('deleted', '!=', 1)->get();
        if($variants){
            foreach ($variants as $k => $variant){
                $res[$k] = $variant;
                $res[$k]->inventory = $inventoryModel->getInventoryByVariantId($variant->_id);
            }
        }
        return $res;
    }

}