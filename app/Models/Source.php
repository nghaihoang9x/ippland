<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Source extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'sources';

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    public function saveSource($data){
        $res = new Source();
        foreach ($data as $key => $val) {
            $res->{$key} = $val;
        }
        $res->save();
        return $res->_id;
    }

    public function updateSource($data, $id){
        $res = Source::find($id);
        foreach ($data as $key => $val) {
            $res->{$key} = $val;
        }
        return $res->update();
    }

    public function listSource($request) {
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $list = Source::where('deleted', '!=', 1);
        if ($keyword) {
            $list->where('title', 'regexp', '/.*'.$keyword.'/i');
        }

        if (is_array($ids) && count($ids)) {
            $list->whereIn('_id', $ids);
        }

        if($request){
            foreach ($request as $k => $v){
                if(!in_array($k, ['ids', 'limit', 'keyword', 'start_date', 'end_date', 'page', 'type', 'api_token'])){
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

}