<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Item extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'products';
    //public static $ATTR = [];


    public function reviews()
    {
        return $this->hasMany('App\Models\Review');
    }

    public function validateSave(){
        return true;
    }

    public function saveItemVendor($product_id, $vendor_id) {

    }

    public function saveCollectionVendor($collections, $vendor_id, $product_id) {

        CollectionVendor::where('product_id',  $product_id)->delete();
        foreach ($collections as $_collection) {
            $_item['collection_id'] = $_collection;
            $_item['vendor_id'] = $vendor_id;
            $_item['product_id'] = $product_id;

            CollectionVendor::forceCreate($_item);
        }
    }

    public function saveItem($data){

        $res = New Item();
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->{$key} = $res->generateSlug($val, 'seo_alias');
            }else if ($key == 'title'){
                $res->{$key} = $val;
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }else if ($key == 'variants'){
                $res->price = $val[0]['regular_price'];
            }
            else $res->{$key} = $val;
        }
        $res->save();
        if ($res->_id) {

            /*$specs = $this->updateSpecs($res->specs);
            $specs_value = $this->updateSpecsValue($res->specs);
            $this->updateItemSpecsValue($res->_id, $specs_value, $res->collections);
            $this->updateCollectionSpecs($res->collections, $specs);*/

            $this->saveCollectionVendor($res->collections, $res->vendor, $res->_id);
            return $res->_id;
        }
    }

    public function updateCollectionSpecs($collections, $specs) {
        foreach ($collections as $_collection) {
            $res = Collection::find($_collection);
            $result = array_merge($res->specs, $specs);
            $arr = array_intersect_key($result, array_unique(array_map('serialize', $result)));
            $res->specs = array_values($arr);
            $res->update();
        }
    }

    public function updateSpecs($specs) {
        $response = [];
        foreach ($specs as $_spec) {
            if ($sp = Specs::where('value', $_spec[0])->first()) {
                $spec['label'] = $sp->label;
                $spec['value'] = $sp->value;
                $response[] = $spec;
                continue;
            }

            $spec['label'] = $_spec[1];
            $spec['value'] = $_spec[0];

            if (Specs::forceCreate($spec))
            {
                $response[] = $spec;
            }
        }
        return $response;
    }

    public function updateSpecsValue($specs) {
        $response = [];
        foreach ($specs as $_spec) {
            if ($sp = SpecsValue::where('spec_value', $_spec[0])->where('value', $_spec[2])->first()) {
                $spec_value['spec_value_id'] = $sp->_id;
                $spec_value['spec_value'] = $sp->spec_value;
                $spec_value['value'] = $sp->value;
                $response[] = $spec_value;
                continue;
            }

            $save_data['spec_value'] = $_spec[0];
            $save_data['value'] = $_spec[2];
            $save = SpecsValue::forceCreate($save_data);
            if ($save)
            {
                $save_value['spec_value_id'] = $save->_id;
                $spec_value['spec_value'] = $save->spec_value;
                $save_value['value'] = $save->value;
                $response[] = $save_value;
            }
        }
        return $response;
    }

    public function updateItemSpecsValue($id, $specs_value, $collection_ids) {
        ItemSpecsValue::where('product_id',  $id)->delete();

        foreach ($collection_ids as $collection_id) {
            foreach ($specs_value as $val) {
                $val['product_id'] = $id;
                $val['collection_id'] = $collection_id;
                ItemSpecsValue::forceCreate($val);
            }
        }
    }

    public function updateItem($data, $id){
        $res = Item::find($id);
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            } elseif ($key == 'price') {
                $res->price = $data['variants'][0]['regular_price'];
            }
            else $res->{$key} = $val;
        }
        $r = $res->update();
        if ($r) {
            /*$specs = $this->updateSpecs($res->specs);
            $specs_value = $this->updateSpecsValue($res->specs);
            $this->updateItemSpecsValue($id, $specs_value, $res->collections);
            $this->updateCollectionSpecs($res->collections, $specs);*/
            $this->saveCollectionVendor($res->collections, $res->vendor, $id);
        }
        return $r;
    }

    public static function listItem($ids, $limit, $keyword = '', $price = '', $brand = ''){
        $categoryModel = new Collection();
        $vendorModel = new Vendor();

        $list = Item::where('deleted', '!=', 1);
        $limit = $limit != '' ? $limit : env('PAGINATION');
        $check = false;
        if (is_array($ids) && count($ids)) {
            $check = true;
            $list = $list->whereIn('_id', $ids);
        }
        $vendorList = [];
        if ($keyword) {
            $list->where('title', 'regexp', '/.*'.$keyword.'/i');
            $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));
            $products = $response->toArray();
            foreach ($products['data'] as $_product) {
                $vendors[] = $_product['vendor'];
            }

            $vendors = array_unique($vendors);
            $vendorList = Vendor::where('deleted', '!=', 1)->whereIn('_id', $vendors)->get();


        }

        if (isset($price) && $price != 0) {
            $price = explode(',', $price);
            $list = $list->whereIn('price', $price);
        }

        if ($brand) {
            $brand = explode(',', $brand);
            //dd($brand);
            $list = $list->whereIn('vendor', $brand);
        }

        $response = $list->orderBy('created_at', 'desc')->paginate(intval($limit));
        $products = $response->toArray();
        $res = [];

        foreach ($products['data'] as $_product) {
            $_product['collections'] = $categoryModel->listCollectionByIds($_product['collections'], $check);
            //$_product['vendor'] = $vendorModel->listVendorById($_product['vendor'], $check);
            //$related = $_product['related_products'];
            //if (array_key_exists('products', $related))
            //if (is_array($related) && count($related)) {
                //$list_product = Item::listItemByIds($related);
                //$_product['related_products'] = $list_product;
            //}
            $res[] = $_product;
        }

        $products['data'] = $res;
        $products['vendor_list'] = $vendorList;

        return $products;
    }

    public static function listItemByIds($ids){
        $categoryModel = New Collection();
        $res = [];
        if (is_array($ids) && count($ids)) {
            $products = Item::whereIn('_id', $ids)->get();
            $i = 0;
            foreach ($products as $_product) {
                $collection = $_product->collections[0];
                $_collection = '';
                if ($collection)
                    $_collection = $categoryModel->listCollectionByIds([$collection]);
                $res[$i] = $_product;

                //$res[$i]['_id'] = $_product->_id;
                //$res[$i]['title'] = $_product->title;
                //$res[$i]['seo_alias'] = $_product->seo_alias;
                //$res[$i]['short_desc'] = $_product->short_desc;
                //$res[$i]['images'] = $_product->images;
                //$res[$i]['variants'] = $_product->variants;
                $res[$i]['collections'] = $_collection;
                //$res[$i]['flashsale'] = $_product->flashsale;
                //$res[$i]['thumb_shape'] = $_product->thumb_shape;
                //$res[$i]['featured'] = $_product->featured;
                //$res[$i]['promotions'] = $_product->promotions;
                $i++;
            }
        }


        return $res;
    }

    public static function listItemByProperties($properties){
        $res = $properties;

        foreach ($properties as $key => $_property) {

            $product = Item::where('_id', $_property['link_to'])->first();
            if (!isset($product->seo_alias)) {
                continue;
            }
            $res[$key]['link_to'] = $product->seo_alias;
        }
        return $res;
    }

    public static function getItemBySlug($slug){
        $_product = Item::where('seo_alias', $slug)->first();

        $categoryModel = new Collection();
        $vendorModel = new Vendor();
        $productModel = new Item();
        $collection = isset($_product->collections[0]) ? $_product->collections[0] : 0;
        $_product['collections'] = $categoryModel->listCollectionByIds($_product->collections);
        $_product['vendor'] = $vendorModel->listVendorById($_product->vendor);

        $properties = $_product->properties;
        $related = $_product->related_products;

        if (is_array($properties) && count($properties)) {
            $list_properties = Item::listItemByProperties($properties);

            $_product->properties = $list_properties;
        }

        if (is_array($related) && count($related)) {
            $list_product = Item::listItemByIds($related);

            $_product->related_products = $list_product;
        } else {
            $_product->related_products = [];
        }

        if ($collection) {
            $compare_products = $productModel->getItemByCollectionId($collection, $compare = true, $_product->_id);

            array_unshift($compare_products['products'], $_product->toArray());

            $_product->compare_products = $compare_products['products'];
        } else {
            $_product->compare_products = [];
        }

        return $_product;

    }


    public function deleteOnly(){
        return $this->delete();
    }

    public function getVariantById($id){
        foreach ($this->variants as $variant) {
            if ($id == $variant['id']) return $variant;
        }

        return null;
    }


    public static function getItemBySlug_Bakup($slug){
        $_product = Item::where('seo_alias', $slug)->first();

        $categoryModel = new Collection();
        $vendorModel = new Vendor();
        $product_collections = Item::where('deleted', '!=', 1)->where('collections', $_product['collections'][0])->get();

        $product_collection_list = [];
        if (isset($product_collections) && count($product_collections)) {
            $i = 0;
            foreach ($product_collections as $_product_collection) {
                if ($i > 4) break;
                $product_collection_list[$i]['_id'] = $_product_collection->_id;
                $product_collection_list[$i]['title'] = $_product_collection->title;
                $product_collection_list[$i]['seo_alias'] = $_product_collection->seo_alias;
                $product_collection_list[$i]['images'] = $_product_collection->images;
                $product_collection_list[$i]['thumb_shape'] = $_product_collection->thumb_shape;
                $product_collection_list[$i]['featured'] = $_product_collection->featured;
                $product_collection_list[$i]['promotions'] = $_product_collection->promotions;
                $i++;
            }
        }

        $_product['collections'] = $categoryModel->listCollectionByIds($_product->collections);
        $_product['vendor'] = $vendorModel->listVendorById($_product->vendor);
        $related = $_product->related_products;
        if (is_array($related) && count($related)) {
            $list_product = Item::listItemByIds($related);
            $_product->related_products = $list_product;
        }
        $_product['product_collection_list'] = $product_collection_list;
        return $_product;

    }

    public function getItemByCollectionId($collection, $compare = false, $product_id = false) {

        $products = Item::where('deleted', '!=', 1)->where('collections', $collection)->get();
        $i = 0;
        $res = [];
        foreach ($products as $_product) {
            if ($compare && $_product->_id == $product_id) {
                continue;
            }

            $res[$i]['_id'] = $_product->_id;
            $res[$i]['title'] = $_product->title;
            $res[$i]['seo_alias'] = $_product->seo_alias;
            $res[$i]['images'] = $_product->images;
            $res[$i]['variants'] = $_product->variants;
            $res[$i]['specs'] = $_product->specs;
            $res[$i]['thumb_shape'] = $_product->thumb_shape;
            $res[$i]['featured'] = $_product->featured;
            $res[$i]['promotions'] = $_product->promotions;
            $i++;
            if ($compare && $i > 3) {
                break;
            }
        }
        return ['products' => $res];
    }

    public function getCaculateItemScore()
    {
        $reviews = Review::where('product_id', (string)$this->_id)->get();
        if (!$reviews) return null;
        $review_number = 0;
        $review_score = 0;
        foreach ($reviews as $key => $review) {
            $review_number++;
            $review_score += $review->score;
        }

        return $review_score / $review_number;
    }
}