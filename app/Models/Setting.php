<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Setting extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'settings';

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    public function saveSetting($data){
        $res = new Setting();
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

    public function updateSetting($data, $id){
        $res = Setting::find($id);
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }
            else $res->{$key} = $val;
        }
        return $res->update();
    }

    public function listSetting($ids, $limit, $type) {
        $list = Setting::where('deleted', '!=', 1);
        if ($type) {
            return Setting::where('type', '=', $type)->first();
        }

        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }
        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));

        return $response->toArray();
    }

    public function listSettingByIds($ids) {
        if (count($ids)) {
            $res = [];
            if (is_array($ids) && count($ids)) {
                $categories = Setting::whereIn('id', $ids)->get();
                $i = 0;
                foreach ($categories as $_Setting) {
                    $res[$i]['label'] = $_Setting->title;
                    $res[$i]['value'] = $_Setting->_id;
                    $i++;
                }
            }
            return $res;
        }
    }

    public static function homepage() {
        $galleryModel = New Gallery();
        $articleModel = New Article();
        $variantModel = new Variant();
        $blogModel    = new Blog();

        $homepage = Setting::where('type', 'homepage')->first();

        if(!$homepage)
            $homepage = new \stdClass();

        //gallery
        $media_blog = $blogModel->getPage(1, 1, 'home_featured');
        /*
        $gallery = $galleryModel->listGallery(['limit' => isset($homepage->media_blog['heading']) && $homepage->media_blog['heading'] > 0 ? round($homepage->media_blog['heading']/2) : 5, 'available' => true]);
        if(isset($gallery['data']) && !empty($gallery['data'])){
            foreach ($gallery['data'] as $k => $item){
                $index = $k * 2;
                $media_blog[$index] = $item;
                $media_blog[$index]['type'] = 'gallery';
            }
        }
        $blog = $articleModel->listArticle(['limit' => isset($homepage->media_blog['heading']) && $homepage->media_blog['heading'] > 0 ? round($homepage->media_blog['heading']/2) : 5, 'available' => true]);

        if(isset($blog['data']) && !empty($blog['data'])){
            foreach ($blog['data'] as $k => $item){
                $index = $k * 2 + 1;
                $media_blog[$index] = $item;
                $media_blog[$index]['type'] = 'blog';
            }
        }
         */
       // if($media_blog) {
            //ksort($media_blog);
           // $media_blog = array_reverse(array_values($media_blog), true);
       // }
        $homepage->media_blog = [
            'heading' => isset($homepage->media_blog['heading']) && $homepage->media_blog['heading'] ? $homepage->media_blog['heading'] : 'Media & Blog',
            'data' => $media_blog
        ];

        //brands
        $brands_ = isset($homepage->brands['items']) && is_array($homepage->brands['items']) ? Vendor::whereIn('_id', $homepage->brands['items'])->where('deleted', '!=', 1)->where('status', '!=', false)->get() : [];
        $homepage->brands = [
            'heading' => isset($homepage->brands['heading']) && $homepage->brands['heading'] ? $homepage->brands['heading'] : 'Các thương hiệu của Jillian',
            'items' => $brands_
        ];

        //testimonial
        $homepage->testimonials = [
            'heading' => isset($homepage->testimonials['heading']) && $homepage->testimonials['heading'] ? $homepage->testimonials['heading'] : 'Các thương hiệu của Jillian',
            'items' => isset($homepage->testimonials['items']) ? $homepage->testimonials['items'] : [],
            'image' => isset($homepage->testimonials['image']) ? $homepage->testimonials['image'] : [],
        ];

        //products
        $collections_data = Collection::where('available', true)->where('deleted', '!=', 1)->where('status', '!=', false)->get(['title', 'title_en', 'title_cn', 'seo_alias']);
        $collections = $variants = [];
        $collections[] = [
            '_id' => '',
            'title' => 'All',
            'seo_alias' => 'all',
        ];
        $get = [
            'limit' => 9,
            'available' => true,
            'featured' => true,
            'status' => true,
            'homepage' => true
        ];
        $variants_ = $variantModel->listVariant($get, true);
        $variants['all'] = isset($variants_['data']) ? $variants_['data'] : [];
        if($collections_data){
            foreach ($collections_data as $collection){
                $collections[] = $collection;

                $get = [
                    'collection' => $collection['_id'],
                    'limit' => 9,
                    'available' => true,
                    'featured' => true,
                    'status' => true,
                    'homepage' => true
                ];
                $variants_ = $variantModel->listVariant($get, true);
                $variants[$collection['seo_alias']] = isset($variants_['data']) ? $variants_['data'] : [];
            }
        }

        $homepage->products = [
            'heading' => isset($homepage->products['heading']) && $homepage->products['heading'] ? $homepage->products['heading'] : 'Sản phẩm của Jillian',
            'categories' => $collections,
            'variants' => $variants
        ];

        return $homepage;

    }

    public function general() {
        $general = Setting::where('type', 'general')->first();
        return $general;
    }
}