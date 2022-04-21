<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Page extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'pages';


    public function validateSave(){
        return true;
    }

    public function savePage($data){
        $res = new Page();
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

    public function updatePage($data, $id){
        $res = Page::find($id);
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }
            else $res->{$key} = $val;
        }
        return $res->update();
    }

    public function listPage($request) {
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $list = Page::where('deleted', '!=', 1);

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
        /*foreach ($Pages['data'] as $key => $_Page) {
            $list_children = $this->getChildren($_Page['_id']);
            if (!$list_children) continue;
            $Pages['data'][$key]['childs'] = $list_children;
        }
        return $Pages;*/
    }

    public function listPageChildren($ids, $limit) {
        $list = Page::where('deleted', '!=', 1);
        $list = $list->where('parent', '=', '');
        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }

        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));

        $Pages = $response->toArray();
        foreach ($Pages['data'] as $key => $_Page) {
            $list_children = $this->getChildren($_Page['_id']);
            if (!$list_children) continue;
            $Pages['data'][$key]['childs'] = $list_children;
        }
        return $Pages;
    }

    public function getPageByAlias($slug) {
        $detail = Page::where('seo_alias', $slug)->where('available', true)->where('status', '!=', false)->first();
        if($detail)
            return ['detail' => $detail];
    }

    public function getChildren($parent_id) {
        $list = Page::where('deleted', '!=', 1);
        $list = $list->where('parent', '=', $parent_id);
        $list = $list->select(['_id']);
        $response = $list->orderBy('updated_at', 'desc')->paginate(intval(1000));;
        $Pages = $response->toArray();
        if (empty($Pages['data'])) return false;
        foreach ($Pages['data'] as $key => $_Page) {
            $list_children = $this->getChildren($_Page['_id']);
            if (!$list_children) continue;
            $Pages['data'][$key]['childs'] = $list_children;
        }
        return $Pages;
    }

    public function listPageByIds($ids) {
        if (count($ids)) {
            $res = [];
            if (is_array($ids) && count($ids)) {
                $categories = Page::whereIn('id', $ids)->get();
                $i = 0;
                foreach ($categories as $_Page) {
                    $res[$i]['label'] = $_Page->title;
                    $res[$i]['value'] = $_Page->_id;
                    $i++;
                }
            }


            return $res;
        }
    }
}