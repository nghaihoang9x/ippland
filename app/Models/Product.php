<?php

namespace App\Models;

use App\Helpers\Common;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;
use App\Models\Geo;

class Product extends Eloquent
{

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'products';
    protected $fillable = [
        'status_sold', 'type_display'
    ];

    public static function boot()
    {
        parent::boot();
        self::retrieved(function ($model) {
            $city = isset($model->address_city) ? $model->address_city . '' : '';
            $state = isset($model->address_state) ? $model->address_state . '' : '';
            $ward = isset($model->address_ward) ? $model->address_ward . '' : '';
            $geos = Geo::whereIn('code', [$city, $state, $ward])->get();
            if (count($geos)) {
                foreach ($geos as $g) {
                    switch ($g->type) {
                        case 'tinh':
                            $model->address_city_display = $g->name;
                            break;
                        case 'huyen':
                            $model->address_district_display = $g->name;
                            break;
                        case 'phuong':
                            $model->address_ward_display = $g->name;

                    }
                }
            }
        });
    }

    public function reviews()
    {
        return $this->hasMany('App\Models\Review');
    }

    public function validateSave()
    {
        return true;
    }

    public function saveProductVendor($product_id, $vendor_id)
    {

    }

    public function saveProductVariant($variants, $product_id, $is_update = false)
    {
        if (!$variants)
            return [];

        Variant::where('product_id', $product_id)->delete();
        foreach ($variants as $variant) {
            $res = new Variant();

            foreach ($variant as $key => $val) {
                $res->{$key} = $val;
            }
            $inventory_data = $variant['inventory'];
            unset($res->inventory);
            $res->product_id = $product_id;

            if ($res->save()) {
                $total = 0;
                foreach ($inventory_data as $item) {
                    $total += intval($item['quantity']);

                    $inventory_ = Inventory::where('variant_id', $res->_id)->where('warehouse_id', $item['id'])->first();
                    if ($inventory_) {
                        $inventory_->quantity = intval($item['quantity']);
                        $inventory_->update();
                    } else {
                        $inventory_ = [
                            'variant_id' => $res->_id,
                            'warehouse_id' => $item['id'],
                            'quantity' => intval($item['quantity'])
                        ];
                        Inventory::forceCreate($inventory_);
                    }
                }

                $variant_current = Variant::find($res->_id);
                $variant_current->quantity = intval($total);
                $variant_current->update();
            }
        }
    }

    public function saveCollectionVendor($collections, $vendor_id, $product_id)
    {
        if ($collections) {
            CollectionVendor::where('product_id', $product_id)->delete();
            foreach ($collections as $_collection) {
                $_item['collection_id'] = $_collection;
                $_item['vendor_id'] = $vendor_id;
                $_item['product_id'] = $product_id;

                CollectionVendor::forceCreate($_item);
            }
        }

    }

    public function saveProduct($data)
    {

        $res = New Product();
        foreach ($data as $key => $val) {
            $val = Common::standardize_model_value_type($val, $key);
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false) {
                $res->{$key} = $res->generateSlug($val, 'seo_alias');
            } else if ($key == 'title') {
                $res->{$key} = $val;
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            } else $res->{$key} = $val;
        }

        $res->save();
        if ($res->_id) {

            /*$specs = $this->updateSpecs($res->specs);
            $specs_value = $this->updateSpecsValue($res->specs);
            $this->updateProductSpecsValue($res->_id, $specs_value, $res->collections);
            $this->updateCollectionSpecs($res->collections, $specs);*/
//            $this->saveCollectionVendor($res->collections, $res->brand, $res->_id);
            return $res->_id;
        }
    }

    public function updateCollectionSpecs($collections, $specs)
    {
        foreach ($collections as $_collection) {
            $res = Collection::find($_collection);
            $result = array_merge($res->specs, $specs);
            $arr = array_intersect_key($result, array_unique(array_map('serialize', $result)));
            $res->specs = array_values($arr);
            $res->update();
        }
    }

    public function updateSpecs($specs)
    {
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

            if (Specs::forceCreate($spec)) {
                $response[] = $spec;
            }
        }
        return $response;
    }

    public function updateSpecsValue($specs)
    {
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
            if ($save) {
                $save_value['spec_value_id'] = $save->_id;
                $spec_value['spec_value'] = $save->spec_value;
                $save_value['value'] = $save->value;
                $response[] = $save_value;
            }
        }
        return $response;
    }

    public function updateProductSpecsValue($id, $specs_value, $collection_ids)
    {
        ProductSpecsValue::where('product_id', $id)->delete();

        foreach ($collection_ids as $collection_id) {
            foreach ($specs_value as $val) {
                $val['product_id'] = $id;
                $val['collection_id'] = $collection_id;
                ProductSpecsValue::forceCreate($val);
            }
        }
    }

    public function updateProduct($data, $id)
    {
        $res = Product::find($id);
        foreach ($data as $key => $val) {
            $val = Common::standardize_model_value_type($val, $key);
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false) {
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            } else $res->{$key} = $val;
        }

        $r = $res->update();
        if ($r) {
            /*$specs = $this->updateSpecs($res->specs);
            $specs_value = $this->updateSpecsValue($res->specs);
            $this->updateProductSpecsValue($id, $specs_value, $res->collections);
            $this->updateCollectionSpecs($res->collections, $specs);*/
//            $this->saveCollectionVendor($res->collections, $res->vendor, $id);
        }
        return $r;
    }

    public static function standardize_query_params($param_name, $request)
    {
        $param_name = strtolower($param_name);
        $value = isset($request[$param_name]) ? $request[$param_name] : '';
        if (empty($value)) return $request;
        if ($param_name == 'address_city') {
            $city = Geo::where('slug', '=', $value)->first();
            if ($city) {
                $request[$param_name] = $city->code;
            }
        }

        if ($param_name == 'project_parent_name') {
            $project = Project::where('seo_alias', '=', $value)->first();
            if ($project) {
                $request[$param_name] = $project->_id;
            }
        }
        return $request;
    }

    public static function listProduct($request)
    {

        $categoryModel = new Collection();

        $ids = isset($request['ids']) ? $request['ids'] : '';
        if ($ids) {
            $ids = explode(',', $ids);
        }
        $limit = isset($request['limit']) ? $request['limit'] : env('PAGINATION');
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        $price = isset($request['price']) ? $request['price'] : '';
        $area = isset($request['area']) ? $request['area'] : '';

        $attach = isset($request['attach']) ? explode(',', $request['attach']) : '';
        $request = self::standardize_query_params('address_city', $request);
        $request = self::standardize_query_params('project_parent_name', $request);
        $list = Product::where('deleted', '!=', 1);
        $check = false;
        if (is_array($ids) && count($ids)) {
            $check = true;
            $list = $list->whereIn('_id', $ids);
        }
        $vendorList = [];
        if ($keyword) {
            $list->where('title', 'regexp', '/.*' . $keyword . '/i');
            $list->orderBy('created_at', 'desc')->paginate(intval($limit));
        }
        if (isset($price) && !empty($price)) {

            $price = explode(',', $price);
            $_min_price = intval($price[0]);
            $_max_price = isset($price[1]) ? intval($price[1]) : 100000000000000;
            $list = $list->where('price', ['$gte' => $_min_price, '$lte' => $_max_price]);
        }
        if (isset($area) && !empty($area)) {

            $area = explode(',', $area);
            $_min_area = intval($area[0]);
            $_max_area = isset($area[1]) ? intval($area[1]) : 1000000000000000;
            $list = $list->where('area', ['$gte' => $_min_area, '$lte' => $_max_area]);
        }

        if ($request) {
            unset($request['options']);
            unset($request['device_token']);
            unset($request['carts']);
            foreach ($request as $k => $v) {
                if (!in_array($k, ['ids', 'limit', 'keyword', 'start_date', 'end_date', 'page',
                    'type', 'options', 'device_token', 'carts', 'query', 'discounts_code', 'plan_id',
                    'work_shift', 'buyer_type', 'api_token', 'brand', 'price', 'attach', 'area', 'city', 'range_1',
                    'floors', 'bedroom', 'block', 'product_ptype', 'product_type'])) {
                    if (empty($v)) continue;
                    if ($v == 'false' || $v == 'true')
                        $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                    $list->where($k, $v);
                }
            }
        }


        if (isset($request['city'])) {
            $city = \App\Models\Geo::getCityBySlug($request['city']);
            if ($city) {
                $code = $city->code;
                // field address_city trong bản product vẫn đang có dữ liệu string (lỗi dữ liệu)
                $list->where('address_city', intval($code));
            }
        }

        if (isset($request['bedroom']) && !empty($request['bedroom'])) {
            $bedrooms = $request['bedroom'];
            $bedrooms = array_map(
                function ($value) {
                    return (int)$value;
                },
                $bedrooms
            );
            $list->whereIn('bedroom', $bedrooms);
        }

        if (isset($request['floors']) && !empty($request['floors'])) {
            $floors = $request['floors'];
            $floors = array_map(
                function ($value) {
                    return (int)$value;
                },
                $floors
            );
            $list->whereIn('floors', $floors);
        }


        if (isset($request['block'])) {
            $list->where('block', 'LIKE', '%' . $request['block'] . '%');
        }

        if (isset($request['product_type'])) {
            $list->where('product_type', $request['product_type']);
        }

//        if (isset($request['product_ptype'])) {
//            $list->where('product_ptype', $request['product_ptype']);
//        }

        $response = $list->orderBy('created_at', 'desc')->paginate(intval($limit));
        $products = $response->toArray();
        $res = [];

        foreach ($products['data'] as $_product) {
            $_product['collections'] = isset($_product['collections']) ? $categoryModel->listCollectionByIds($_product['collections'], $check) : [];
            if ($attach) {
                foreach ($attach as $_a) {
                    switch ($_a) {
                        case 'project':
                            $_project = Project::where('_id', $_product['project_parent_name'])->first();
                            $_product['project'] = ['title' => $_project->title, '_id' => $_project->_id];
                            break;
                        case 'customer':
                            if (isset($_product['user_id'])) {
                                $_user = User::where('_id', $_product['user_id'])->first();
                                $_product['user'] = ['phone' => $_user->phone, '_id' => $_user->_id];
                            }
                            break;
                    }
                }
            }
            $res[] = $_product;
        }

        $products['data'] = $res;
        $products['vendor_list'] = $vendorList;

        return $products;
    }

    public static function listProductByIds($ids)
    {
        $categoryModel = New Collection();

        $vendorModel = new Vendor();
        $res = [];
        if (is_array($ids) && count($ids)) {
            $products = Product::whereIn('_id', $ids)->where('available', true)->where('status', '!=', false)->get();
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

                $res[$i]['collections'] = $_collection;

                if (isset($_product['brand']))
                    $res[$i]['brand'] = $vendorModel->listBrandById($_product['brand']);
                //$res[$i]['flashsale'] = $_product->flashsale;
                //$res[$i]['thumb_shape'] = $_product->thumb_shape;
                //$res[$i]['featured'] = $_product->featured;
                //$res[$i]['promotions'] = $_product->promotions;
                $i++;
            }
        }


        return $res;
    }

    public static function listProductByProperties($properties)
    {
        $res = $properties;

        foreach ($properties as $key => $_property) {

            $product = Product::where('_id', $_property['link_to'])->first();
            if (!isset($product->seo_alias)) {
                continue;
            }
            $res[$key]['link_to'] = $product->seo_alias;
        }
        return $res;
    }

    public static function getProductBySlug($slug)
    {
        $_product = Product::where('seo_alias', $slug)->first();
//        $_product = Product::where('seo_alias', $slug)->where('available', true)->where('status', '!=', false)->first();

        $categoryModel = new Collection();
        $vendorModel = new Vendor();

        $reviewModel = new Review();

        $properties = $_product->properties;
        $related = $_product->related_products;

        if (is_array($properties) && count($properties)) {
            $list_properties = Product::listProductByProperties($properties);

            $_product->properties = $list_properties;
        }

//        if (is_array($related) && count($related)) {
//            $list_product = Product::listProductByIds($related);
//
//            $_product->related_products = $list_product;
//        } else {
//            $list_product = Product::listProductByCollection($_product->_id, $_product->collections);
//            $_product->related_products = $list_product;
//        }

        $_product['collections'] = $categoryModel->listCollectionByIds($_product->collections);
        $_product['review'] = $reviewModel->general($_product->_id);

        if (isset($_product->brand))
            $_product->brand = $vendorModel->listBrandById($_product->brand, ['id', 'title', 'seo_alias', 'images']);
        return $_product;

    }

    public static function listProductByCollection($id, $collections)
    {

        $vendorModel = new Vendor();
        $res = [];

        $collections = is_string($collections) ? [$collections] : $collections;
        $products = Product::whereIn('collections', $collections)->where('_id', '!=', $id)->where('available', true)->where('status', '!=', false)->limit(8)->get();

        $categoryModel = new Collection();
        $i = 0;
        foreach ($products as $_product) {
            $collection = $_product->collections[0];
            $_collection = '';
            if ($collection)
                $_collection = $categoryModel->listCollectionByIds([$collection]);
            $res[$i] = $_product;

            $res[$i]['collections'] = $_collection;

            if (isset($_product['brand']))
                $res[$i]['brand'] = $vendorModel->listBrandById($_product['brand']);
            $i++;
        }
        return $res;
    }


    public function deleteOnly()
    {
        return $this->delete();
    }

    public function getVariantById($id)
    {
        $variant = Variant::where(function ($q) use ($id) {
            $q->orWhere('_id', $id)
                ->orWhere('id', $id)
                ->orWhere('variant_id', $id);
        })->first();
        if ($variant)
            return $variant;

        return null;
    }


    public static function getProductBySlug_Bakup($slug)
    {
        $_product = Product::where('seo_alias', $slug)->first();

        $categoryModel = new Collection();
        $vendorModel = new Vendor();
        $product_collections = Product::where('deleted', '!=', 1)->where('collections', $_product['collections'][0])->get();

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
        $_product['brand'] = $vendorModel->listVendorById($_product->brand);
        $related = $_product->related_products;
        if (is_array($related) && count($related)) {
            $list_product = Product::listProductByIds($related);
            $_product->related_products = $list_product;
        }
        $_product['product_collection_list'] = $product_collection_list;
        return $_product;

    }

    public function getProductByCollectionId($collection, $request)
    {
        // Filtering
        $products = Product::where('deleted', '!=', 1)->where('available', true)->where('status', '!=', false)->where('collections', $collection);
        if (!empty($project_slug = $request->get('project_slug', ''))) {
            $project = Project::where('seo_alias', $project_slug)->first();
            if (!$project) return ['products' => null];
            $products->where('project', $project->_id);
        }

        $products = $products->get();
        $products = $products->toArray();
        return ['products' => $products];
    }

    public function getCaculateProductScore()
    {
        $reviews = Review::where('product_id', (string)$this->_id)->where('available', true)->get();
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