<?php

namespace App\Models;

use App\Helpers\Common;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Lead extends Eloquent
{

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'leads';
    //public static $ATTR = [];
    protected $fillable = [
        'deleted'
    ];


    public function validateSave()
    {
        return true;
    }

    public function saveLead($data)
    {

        $res = New Lead();
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
            $this->updateLeadSpecsValue($res->_id, $specs_value, $res->collections);
            $this->updateCollectionSpecs($res->collections, $specs);*/
//            $this->saveCollectionVendor($res->collections, $res->brand, $res->_id);
            return $res->_id;
        }
    }


    public function updateLead($data, $id)
    {
        $res = Lead::find($id);
        foreach ($data as $key => $val) {
            $val = Common::standardize_model_value_type($val, $key);
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false) {
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            } else $res->{$key} = $val;
        }

        $r = $res->update();
        if ($r) {
            return $r;
        }
        return false;

    }

    public static function listLead($request)
    {
        $categoryModel = new Collection();

        $ids = isset($request['ids']) ? $request['ids'] : '';
        if ($ids) {
            $ids = explode(',', $ids);
        }
        $limit = isset($request['limit']) ? $request['limit'] : env('PAGINATION');
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        $brand = isset($request['brand']) ? $request['brand'] : '';
        $price = isset($request['price']) ? $request['price'] : '';
        $attach = isset($request['attach']) ? explode(',', $request['attach']) : '';
        $list = Lead::where('deleted', '!=', 1);
        $check = false;
        if (isset($request['user_id'])) {
            $user_id = $request['user_id'];
            $list->where(function ($q) use ($user_id) {
                $q->where('assign_user_id', $user_id)
                    ->orWhere('user_id', $user_id);
            });
        }
        if (is_array($ids) && count($ids)) {
            $check = true;
            $list = $list->whereIn('_id', $ids);
        }
        if ($keyword) {
            $list->where(function ($query) use ($keyword) {
                $query->where('title', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('customer_name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('customer_address', 'LIKE', '%' . $keyword . '%');
            });
//            $list->orderBy('created_at', 'desc')->paginate(intval($limit));
        }


        if (isset($price) && $price != 0) {
            $price = explode(',', $price);
            $list = $list->whereIn('price', $price);
        }

        if ($brand) {
            $brand = explode(',', $brand);
            //dd($brand);
            $list = $list->whereIn('brand', $brand);
        }
        if ($request) {
            unset($request['options']);
            unset($request['device_token']);
            unset($request['carts']);
            foreach ($request as $k => $v) {
                if (!in_array($k, ['ids', 'limit', 'keyword', 'start_date', 'end_date', 'page', 'type',
                    'options', 'device_token', 'carts', 'query', 'discounts_code', 'plan_id', 'work_shift',
                    'buyer_type', 'api_token', 'brand', 'price', 'attach', 'user_id', 'limit', 'city', 'ptype'])) {
                    if ($v == 'false' || $v == 'true')
                        $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                    $list->where($k, $v);
                }
            }
        }

        if (isset($request['city'])) {
            $city = Geo::getCityBySlug($request['city']);
            if ($city) {
                $list->where('address_city', $city->code);
            }
        }

        if (isset($request['ptype']) && $request['ptype']) {
            $list->where('product_ptype', $request['ptype']);
        }

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
                            $_user = User::where('_id', $_product['user_id'])->first();
                            $_product['user'] = ['phone' => $_user->phone, '_id' => $_user->_id];
                            break;
                    }
                }
            }


            $res[] = $_product;
        }

        $products['data'] = $res;
        return $products;
    }

    public static function listLeadByIds($ids)
    {
        $categoryModel = New Collection();

        $res = [];
        if (is_array($ids) && count($ids)) {
            $products = Lead::whereIn('_id', $ids)->where('available', true)->where('status', '!=', false)->get();
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

                //$res[$i]['flashsale'] = $_product->flashsale;
                //$res[$i]['thumb_shape'] = $_product->thumb_shape;
                //$res[$i]['featured'] = $_product->featured;
                //$res[$i]['promotions'] = $_product->promotions;
                $i++;
            }
        }


        return $res;
    }

    public static function listLeadByProperties($properties)
    {
        $res = $properties;

        foreach ($properties as $key => $_property) {

            $product = Lead::where('_id', $_property['link_to'])->first();
            if (!isset($product->seo_alias)) {
                continue;
            }
            $res[$key]['link_to'] = $product->seo_alias;
        }
        return $res;
    }

    public static function getLeadBySlug($slug)
    {
        $_product = Lead::where('seo_alias', $slug)->first();
//        $_product = Lead::where('seo_alias', $slug)->where('available', true)->where('status', '!=', false)->first();

        $categoryModel = new Collection();

        $reviewModel = new Review();

        $properties = $_product->properties;
        $related = $_product->related_products;

        if (is_array($properties) && count($properties)) {
            $list_properties = Lead::listLeadByProperties($properties);

            $_product->properties = $list_properties;
        }

        if (is_array($related) && count($related)) {
            $list_product = Lead::listLeadByIds($related);

            $_product->related_products = $list_product;
        } else {
            $list_product = Lead::listLeadByCollection($_product->_id, $_product->collections);
            $_product->related_products = $list_product;
        }

        $_product['collections'] = $categoryModel->listCollectionByIds($_product->collections);
        $_product['review'] = $reviewModel->general($_product->_id);

        return $_product;

    }

    public static function listLeadByCollection($id, $collections)
    {

        $res = [];

        $collections = is_string($collections) ? [$collections] : $collections;
        $products = Lead::whereIn('collections', $collections)->where('_id', '!=', $id)->where('available', true)->where('status', '!=', false)->limit(8)->get();

        $categoryModel = new Collection();
        $i = 0;
        foreach ($products as $_product) {
            $collection = $_product->collections[0];
            $_collection = '';
            if ($collection)
                $_collection = $categoryModel->listCollectionByIds([$collection]);
            $res[$i] = $_product;

            $res[$i]['collections'] = $_collection;

            $i++;
        }
        return $res;
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


    public function getLeadByCollectionId($collection, $request)
    {
        // Filtering
        $products = Lead::where('deleted', '!=', 1)->where('available', true)->where('status', '!=', false)->where('collections', $collection);
        if (!empty($project_slug = $request->get('project_slug', ''))) {
            $project = Project::where('seo_alias', $project_slug)->first();
            if (!$project) return ['products' => null];
            $products->where('project', $project->_id);
        }

        $products = $products->get();
        $products = $products->toArray();
        return ['products' => $products];
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function getById($id)
    {
        $lead = self::find($id);

        return $lead;
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function deleteOnly($id)
    {
        $lead = self::find($id)->update([
            'deleted' => 1
        ]);

        return $lead;
    }


}