<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;
use MongoDB\BSON\UTCDateTime;

class Gallery extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'gallery';

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    protected static function boot()
    {
        parent::boot();
        self::saving(function ($model) {
            if(isset($model->published)){
                $model->published = new UTCDateTime(strtotime($model->published) * 1000);
            }
        });
    }

    public function getPublishedAttribute()
    {
        return isset($this->attributes['published']) && $this->attributes['published'] instanceof UTCDateTime ? $this->attributes['published']->toDateTime()->format('Y-m-d H:i:s') : $this->attributes['published'];
    }

    public function saveGallery($data){
        $res = new Gallery();
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->{$key} = $res->generateSlug($val, 'seo_alias');
            }else if ($key == 'title'){
                $res->{$key} = $val;
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }else
                $res->{$key} = $val;
        }
        $res->save();
        return $res->_id;
    }

    public function updateGallery($data, $id){
        $res = Gallery::find($id);
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }
            else
                $res->{$key} = $val;
        }
        return $res->update();
    }

    public function listGallery($request) {
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $list = Gallery::where('deleted', '!=', 1);
        if ($keyword) {
            $list->where('title', 'regexp', '/.*'.$keyword.'/i');
        }

        if (is_array($ids) && count($ids)) {
            $list->whereIn('_id', $ids);
        }

        if($request){
            foreach ($request as $k => $v){
                if(!in_array($k, ['ids', 'limit', 'keyword', 'start_date', 'end_date', 'page', 'type', 'api_token', 'home'])){
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

        $response = $list->orderBy('published', 'desc')->paginate(intval($limit));
        $res = $response->toArray();

        return $res;
    }

    public function getGallery($homepage = false, $request){
        $slider = Gallery::where('deleted', '!=', 1)->where('available', true)->where('status', '!=', false)->where('featured', true)->get();
        if($homepage)
            return $slider;
        $gallery = Gallery::listGallery($request);
        $res = [
            'slider' => $slider,
            'gallery' => $gallery
        ];
        return $res;
    }
}