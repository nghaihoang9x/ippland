<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Collection extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'collections';
    private $paginate = 5;

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    public function saveCollection($data){
        $res = new Collection();

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
        if ($res->_id) {
            $item = [];
            $item['collection_id'] = $res->_id;
            $item['parent_id'] = $data['parent'];

            CollectionItem::forceCreate($item);
        }

        return $res->_id;
    }

    public function updateCollection($data, $id){
        $res = Collection::find($id);
        $exist = CollectionItem::where('parent_id', $id)->first();

        if ($exist) {
            $data['has_children'] = 1;
        }

        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }
            else $res->{$key} = $val;
        }
        $update = $res->update();
        if ($update) {
            $item = [];
            $item['collection_id'] = $id;
            $item['parent_id'] = isset($data['parent']) ? $data['parent'] : '';

            CollectionItem::where('collection_id', $id)->delete();
            CollectionItem::forceCreate($item);
        }
        return $update;
    }

    public function listCollection($request) {
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        $parent = isset($request['parent']) ? $request['parent'] : '';

        if ($ids) {
            $ids = explode(',', $ids);
        }

        $list = Collection::where('deleted', '!=', 1);

        if ($parent) {
            $list->where('has_children', 'exists' , false);
        }

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
        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));

        $collections = $response->toArray();
        $res = [];
        foreach ($collections['data'] as $_collection) {
            if ($_collection['parent']) {
                if ($parent = Collection::where('_id', $_collection['parent'])->where('available', true)->where('status', '!=', false)->where('deleted', '!=', 1)->first())
                    $_collection['parent_name'] = $parent->title;
                else
                    $_collection['parent_name'] = '';
            }
            $_collection['url'] = 'https://jil.site4com.net/vi/' . $_collection['seo_alias'];

            $res[] = $_collection;
        }

        $collections['data'] = $res;

        return $collections;
    }

    public function listCollectionChildren($ids, $limit) {
        $list = Collection::where('deleted', '!=', 1);
        $list = $list->where('parent', '=', '');
        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }

        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));

        $collections = $response->toArray();
        foreach ($collections['data'] as $key => $_collection) {
            $list_children = $this->getChildren($_collection['_id']);
            if (!$list_children) continue;
            $collections['data'][$key]['childs'] = $list_children;
        }
        return $collections;
    }

    public function getProductBySlug($slug, $request, $type = 'DESC') {
        $detail = Collection::where('seo_alias', $slug)->where('available', true)->where('status', '!=', false)->first();
        $variant = new Variant();
        $request['collection'] = $detail->_id;
        $products = $variant->listVariant($request);
        return ['detail' => $detail, 'products' => $products];
    }

    public function getChildren($parent_id) {
        $list = Collection::where('deleted', '!=', 1)->where();
        $list = $list->where('parent', '=', $parent_id);
        $list = $list->select(['_id']);
        $response = $list->orderBy('updated_at', 'desc')->paginate(intval(1000));;
        $collections = $response->toArray();
        if (empty($collections['data'])) return false;
        foreach ($collections['data'] as $key => $_collection) {
            $list_children = $this->getChildren($_collection['_id']);
            if (!$list_children) continue;
            $collections['data'][$key]['childs'] = $list_children;
        }
        return $collections;
    }

    public function listCollectionByIds($ids, $list = false) {
        if ($list) {
            return $ids;
        }
        if (!is_array($ids)) $ids = [$ids];
        if (count($ids)) {
            $res = [];
            if (is_array($ids) && count($ids)) {
                $categories = Collection::whereIn('_id', $ids)->get();
                $i = 0;
                foreach ($categories as $_collection) {
                    $res[$i]['label'] = $_collection->title;
                    if (isset($_collection->title_en)) $res[$i]['label_en'] = $_collection->title_en;
                    if (isset( $_collection->title_cn)) $res[$i]['label_cn'] = $_collection->title_cn;
                    $res[$i]['value'] = $_collection->_id;
                    $res[$i]['seo_alias'] = $_collection->seo_alias;
                    $res[$i]['images'] = $_collection->images;
                    $i++;
                }
            }
            return $res;
        }
    }

    public function father()
    {
        return $this->belongsTo(self::class, 'parent');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent');
    }

    public static function getAllCollection($is_array = true) {
        $collections = Collection::where('deleted', '!=', 1)->where('parent', '=', '')->with('children')->get();

        // return $is_array ? array_map(function($value) {return (array)$value;}, $collections->toArray()) : $collections;
        return $is_array ? $collections->toArray() : $collections;
    }

    public static function loadSpecsFilter($collection_id) {
        $collections = Collection::find($collection_id);

        $data = ProductSpecsValue::where('collection_id', $collection_id)->get();
        $selected_specs = $collections->selected_specs;
        $specs = [];
        foreach ($data as $_data) {

            //if ($_data['spec_value'] == 'radius') {
                //var_dump($_data['spec_value_id']);
            //}
            if (!in_array($_data['spec_value'], $selected_specs)) continue;

            if (isset($specs[$_data['spec_value']][$_data['spec_value_id']])) {
                $specs[$_data['spec_value']][$_data['spec_value_id']]['data']['total'] += 1;
            } else {
                $_data['total'] = 1;
                $specs[$_data['spec_value']][$_data['spec_value_id']]['data'] = $_data;
                //$specs[$_data['spec_value']][$_data['spec_value_id']]['total'] = 1;
            }
        }

        //var_dump($specs);die;
        //die;
        return $specs;
    }


    //new function for box
    public function listItems($ids, $page) {
        $list = Collection::where('deleted', '!=', 1);
        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }
        $response = $list->orderBy('updated_at', 'desc')->get();
        $limit = env('PAGINATION');
        $collections = $response->toArray();
        $data = [];
        foreach ($collections as $id => $_collection) {
            //dd($_collection);
            if (strpos($_collection['seo_alias'], 'wildcard') !== false) {
                $collections[$id]['products'] = Product::where('deleted', '!=', 1)->paginate(intval($limit), ['*'], 'page', $page);
                continue;
            }
            $collections[$id]['products'] = Product::where('deleted', '!=', 1)->where('collections', $_collection['_id'])
                ->paginate(intval($limit), ['*'], 'page', $page);
            //$data[$_collection['_id']] = $collections[$id];
        }
        dd($collections);
    }

    //new function for box
    public function list($ids, $page) {
        $data = [];
        $i = 0;
        $limit = env('PAGINATION');
        foreach ($ids as $id) {
            //dd($_collection);
            $data[$i] = Collection::where('deleted', '!=', 1)->where('available', true)->where('status', '!=', false)->where('_id', $id)->first()->toArray();

            if (strpos($data[$i]['seo_alias'], 'wildcard') !== false) {
                $data[$i]['products'] = Product::where('deleted', '!=', 1)->where('available', true)->where('status', '!=', false)->where('wildcard', 1)->paginate(intval($limit), ['*'], 'page', $page);
            } else {

                $data[$i]['products'] = Product::where('deleted', '!=', 1)->where('status', '!=', false)->where('available', true)->where('collections', $data[$i]['_id'])
                    ->paginate(intval($limit), ['*'], 'page', $page);
            }
            $i++;
        }
        return $data;
    }

    public function homeList(){
        $collections = Collection::where('available', true)->where('status', '!=', false)->where('deleted', '!=', 1)->get(['title', 'seo_alias']);
        $res = [];
        $res[] = [
            '_id' => '',
            'title' => 'All',
            'seo_alias' => 'all',
        ];
        if($collections){
            foreach ($collections as $collection){
                $res[] = [
                    '_id' => $collection->_id,
                    'title' => $collection->title,
                    'seo_alias' => $collection->seo_alias,
                ];
            }
        }
        return $res;
    }
}