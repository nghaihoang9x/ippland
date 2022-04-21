<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;
use MongoDB\BSON\UTCDateTime;

class Article extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'articles';

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

    public function saveArticle($data){
        $res = new Article();
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

    public function updateArticle($data, $id){
        $res = Article::find($id);
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }
            else $res->{$key} = $val;
        }
        return $res->update();
    }

    public function listArticle($request) {
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';

        $list = Article::where('deleted', '!=', 1);
        if ($keyword) {
            $list->where('title', 'regexp', '/.*'.$keyword.'/i');
        }
        $limit = $limit != '' ? $limit : env('PAGINATION');
        if ($ids) {
            $ids = explode(',', $ids);
        }
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
        $response = $list->orderBy('published', 'desc')->paginate(intval($limit));

        return $response->toArray();
        /*foreach ($articles['data'] as $key => $_article) {
            $related = $this->getRelatedArticle($_article['_id']);
            if (!$list_children) continue;
            $articles['data'][$key]['childs'] = $list_children;
        }
        return $Articles;*/
    }


    public function getRelatedArticle($article_id, $blog_id) {
        $datas = Article::where('blog', $blog_id)->where('deleted', '!=', 1)->where('status', '!=', false)->where('available', true)->where('_id', '!=', $article_id)->orderBy('published', 'desc')->limit(4)->get();
        $res = [];
        foreach ($datas as $data) {
            $blog = Blog::where('_id', (string)$data->blog)->where('status', '!=', false)->first(['title', 'seo_alias', 'images']);
            $data->blog = $blog;
            if(isset($data->source))
                $source_ = Source::where('_id', $data->source)->where('status', '!=', false)->first(['title', 'link', 'images']);
            else
                $source_ = [];

            $data->source = $source_;
            $res[] = $data;
        }
        return $res;
    }

    public function listArticleChildren($ids, $limit) {
        $list = Article::where('deleted', '!=', 1);
        $list = $list->where('parent', '=', '');
        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }

        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));

        $Articles = $response->toArray();
        foreach ($Articles['data'] as $key => $_Article) {
            $list_children = $this->getChildren($_Article['_id']);
            if (!$list_children) continue;
            $Articles['data'][$key]['childs'] = $list_children;
        }
        return $Articles;
    }

    public function getArticleBySlug($slug) {
        $detail = Article::where('seo_alias', $slug)->where('available', true)->where('status', '!=', false)->first();
        $detail->related = $this->getRelatedArticle($detail->_id, $detail->blog);
        $detail->blog = Blog::where('_id', $detail->blog)->where('available', true)->where('status', '!=', false)->first(['seo_alias', 'title']);
        if(isset($detail->source))
            $source_ = Source::where('_id', $detail->source)->where('available', true)->where('status', '!=', false)->first(['title', 'link', 'images']);
        else
            $source_ = [];

        $detail->source = $source_;

        return ['detail' => $detail];
    }

    public function getChildren($parent_id) {
        $list = Article::where('deleted', '!=', 1);
        $list = $list->where('parent', '=', $parent_id);
        $list = $list->select(['_id']);
        $response = $list->orderBy('updated_at', 'desc')->paginate(intval(1000));;
        $Articles = $response->toArray();
        if (empty($Articles['data'])) return false;
        foreach ($Articles['data'] as $key => $_Article) {
            $list_children = $this->getChildren($_Article['_id']);
            if (!$list_children) continue;
            $Articles['data'][$key]['childs'] = $list_children;
        }
        return $Articles;
    }

    public function listArticleByIds($ids) {
        if (count($ids)) {
            $res = [];
            if (is_array($ids) && count($ids)) {
                $categories = Article::whereIn('id', $ids)->where('deleted', '!=', 1)   ->get();
                $i = 0;
                foreach ($categories as $_Article) {
                    $res[$i]['label'] = $_Article->title;
                    $res[$i]['value'] = $_Article->_id;
                    $i++;
                }
            }
            return $res;
        }
    }

    public static function getByBlogId($blog_id, $request = null) {
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $limit = $limit != '' ? $limit : env('PAGINATION');
        $list = Article::where('deleted', '!=', 1)->where('available', true)->where('status', '!=', false);
        if ($blog_id) {
            $list->where('blog', $blog_id);
        }

        $data = $list->orderBy('published', 'desc')->paginate(intval($limit))->toArray();;

        $new_data = $data;
        foreach ($data['data'] as $k => $_item) {
            $new_data['data'][$k]['blog'] = Blog::where('_id', $_item['blog'])->first(['seo_alias', 'title']);

            if(isset($_item['source']))
                $source_ = Source::where('_id', $_item['source'])->first(['title', 'link', 'images']);
            else
                $source_ = [];
            $new_data['data'][$k]['source'] = $source_;
        }

        return $new_data;
    }

    public static function getBlogNotNews($news_id, $request) {
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $limit = $limit != '' ? $limit : env('PAGINATION');
        $list = Article::where('deleted', '!=', 1)->where('status', '!=', false)->where('available', true);
        if ($news_id) {
            $list->where('blog', '!=', $news_id);
        }

        $data = $list->orderBy('published', 'desc')->paginate(intval($limit))->toArray();;

        $new_data = $data;
        foreach ($data['data'] as $k => $_item) {
            $new_data['data'][$k]['blog'] = Blog::where('_id', $_item['blog'])->first(['seo_alias', 'title']);
        }

        return $new_data;
    }
}