<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Service extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'services';
    
    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    public function saveService($data){
        $res = new Service();
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

    public function updateService($data, $id){
        $res = Service::find($id);
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }
            else $res->{$key} = $val;
        }
        return $res->update();
    }

    public function listService($ids, $limit, $keyword = false) {
        $list = Service::where('deleted', '!=', 1);
        if ($keyword) {
            $list->where('title', 'regexp', '/.*'.$keyword.'/i');
        }
        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }
        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));

        return $response->toArray();
        /*foreach ($Services['data'] as $key => $_Service) {
            $related = $this->getRelatedService($_Service['_id']);
            if (!$list_children) continue;
            $Services['data'][$key]['childs'] = $list_children;
        }
        return $Services;*/
    }


    public function getRelatedService($Service_id, $blog_id) {
        $datas = Service::where('blog', $blog_id)->where('_id', '!=', $Service_id)->limit(5)->get();
        $res = [];
        foreach ($datas as $data) {
            $blog = Blog::find($data['blog'])->first(['title', 'seo_alias', 'images']);
            $data['blogs'] = $blog;
            $res[] = $data;
        }
        return $res;
    }

    public function listServiceChildren($ids, $limit) {
        $list = Service::where('deleted', '!=', 1);
        $list = $list->where('parent', '=', '');
        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }

        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));

        $Services = $response->toArray();
        foreach ($Services['data'] as $key => $_Service) {
            $list_children = $this->getChildren($_Service['_id']);
            if (!$list_children) continue;
            $Services['data'][$key]['childs'] = $list_children;
        }
        return $Services;
    }

    public function getServiceBySlug($slug) {
        $detail = Service::where('seo_alias', $slug)->first();

        $detail->related = $this->getRelatedService($detail->_id, $detail->blog);
        return ['detail' => $detail];
    }

    public function getChildren($parent_id) {
        $list = Service::where('deleted', '!=', 1);
        $list = $list->where('parent', '=', $parent_id);
        $list = $list->select(['_id']);
        $response = $list->orderBy('updated_at', 'desc')->paginate(intval(1000));;
        $Services = $response->toArray();
        if (empty($Services['data'])) return false;
        foreach ($Services['data'] as $key => $_Service) {
            $list_children = $this->getChildren($_Service['_id']);
            if (!$list_children) continue;
            $Services['data'][$key]['childs'] = $list_children;
        }
        return $Services;
    }

    public function listServiceByIds($ids) {
        if (count($ids)) {
            $res = [];
            if (is_array($ids) && count($ids)) {
                $categories = Service::whereIn('id', $ids)->get();
                $i = 0;
                foreach ($categories as $_Service) {
                    $res[$i]['label'] = $_Service->title;
                    $res[$i]['value'] = $_Service->_id;
                    $i++;
                }
            }


            return $res;
        }
    }
}