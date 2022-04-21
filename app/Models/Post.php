<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Post extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'posts';

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    public function save($data){
        $res = new Post();
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->{$key} = $res->generateSlug($val, 'seo_alias');
            }else if ($key == 'title'){
                $res->{$key} = $val;
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }else
                $res->{$key} = $val;
        }

        if ($res->save()) {
            $category_items_ids = $data['category_items_ids'];
            foreach ($category_items_ids as $value) {
                $item['parent_category_id']      = $data['category_id'];
                $item['category_item_id']   = $value;
                $item['post_id']      = $res->_id;
                PostMetaItemData::forceCreate($item);
            }

        }
        return $res->_id;
    }

    public function update($data, $id){
        $res = Post::find($id);
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }
            else
                $res->{$key} = $val;
        }
        return $res->update();
    }

    public function list($request) {
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $list = Post::where('deleted', '!=', 1);
        if ($keyword) {
            $list->where('title', 'regexp', '/.*'.$keyword.'/i');
        }

        if (is_array($ids) && count($ids)) {
            $list->whereIn('_id', $ids);
        }

        if($request){
            foreach ($request as $k => $v){
                if(!in_array($k, ['ids', 'limit', 'keyword', 'start_date', 'end_date', 'page', 'Post', 'api_token'])){
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

        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));

        return $response->toArray();
    }

    public static function getById($id){
        return Post::find($id)->get(['title', 'seo_alias']);
    }

}
