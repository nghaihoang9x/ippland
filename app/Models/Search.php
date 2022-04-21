<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Search extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'searchs';

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    public function saveSearch($data){
        $res = new Search();
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->{$key} = $res->generateSlug($val, 'seo_alias');
            }else if ($key == 'title'){
                $res->{$key} = $val;
                $res->seo_alias = $res->generateSlug($val, 'seo_alias', true);
                $exist = Search::where('seo_alias', $res->seo_alias)->first();
                if ($exist) return;
            }
            else $res->{$key} = $val;
        }
        $res->save();
        return $res->_id;
    }

    public function updateSearch($data, $id){
        $res = Search::find($id);
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }
            else $res->{$key} = $val;
        }
        return $res->update();
    }

    public function listSearch($ids, $limit, $keyword = false) {
        $list = Search::where('deleted', '!=', 1);

        if ($keyword) {
            $list->where('title', 'regexp', '/.*'.$keyword.'/i');
        }

        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }
        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));

        return $response->toArray();
    }

    public function listSearchByIds($ids) {
        if (count($ids)) {
            $res = [];
            if (is_array($ids) && count($ids)) {
                $categories = Search::whereIn('id', $ids)->get();
                $i = 0;
                foreach ($categories as $_Search) {
                    $res[$i]['label'] = $_Search->title;
                    $res[$i]['value'] = $_Search->_id;
                    $i++;
                }
            }
            return $res;
        }
    }
}