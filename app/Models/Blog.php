<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Blog extends Eloquent {
    use SlugTrait;

    protected $connection = 'mongodb';
    protected $collection = 'blogs';
    
    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    public function saveBlog($data){
        $res = new Blog();
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

    public function updateBlog($data, $id){
        $res = Blog::find($id);
        foreach ($data as $key => $val) {
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false){
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            }
            else $res->{$key} = $val;
        }
        return $res->update();
    }

    public function listBlog($request) {
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $list = Blog::where('deleted', '!=', 1);

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
        /*foreach ($Blogs['data'] as $key => $_Blog) {
            $list_children = $this->getChildren($_Blog['_id']);
            if (!$list_children) continue;
            $Blogs['data'][$key]['childs'] = $list_children;
        }
        return $Blogs;*/
    }

    public function listBlogChildren($ids, $limit) {
        $list = Blog::where('deleted', '!=', 1);
        $list = $list->where('parent', '=', '');
        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }

        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));

        $Blogs = $response->toArray();
        foreach ($Blogs['data'] as $key => $_Blog) {
            $list_children = $this->getChildren($_Blog['_id']);
            if (!$list_children) continue;
            $Blogs['data'][$key]['childs'] = $list_children;
        }
        return $Blogs;
    }

    public function getBlogBySlug($slug, $request) {

        if($slug == 'blog'){

            $news = Blog::where('seo_alias', 'bao-chi')->first();
            $blogs = Blog::where('deleted', '!=', 1)->where('status', '!=', false)->where('available', true);
            if(isset($news->_id))
                $blogs->where('_id', '!=', $news->_id);
            $blogs = $blogs->get()->toArray();
            $articles = Article::getBlogNotNews(isset($news->_id) ? $news->_id : '', $request);

            return [
                'blogs' => $blogs,
                'detail' => [],
                'articles' => $articles,
            ];

        }else {

            $detail = Blog::where('seo_alias', $slug)->where('available', true)->where('status', '!=', false)->first();

            $blog_id = $detail->_id;
            $blogs = Blog::where('deleted', '!=', '1')->where('status', '!=', false)->where('available', true)->get()->toArray();
            $articles = Article::getByBlogId($blog_id, $request);
            return [
                'blogs' => $blogs,
                'detail' => $detail,
                'articles' => $articles,
            ];
        }
    }

    public function getHome() {

        $blogs = Blog::where('deleted', '!=' , 1)->where('status', '!=' , false)->where('available', true)->limit(9)->get()->toArray();

        return $blogs;
    }

    public function getBlog($seo_alias = false) {
        $blog_id = 0;
        if ($seo_alias) {

            $blog = Blog::where('seo_alias', $seo_alias)->where('available', true)->where('status', '!=', false)->first();
            $blog_id = $blog->_id;
            $blogs = Blog::where('seo_alias', '!=' , $seo_alias)->where('deleted', '!=' , 1)->where('status', '!=' , false)->where('available', true)->get()->toArray();
        } else {
            $blogs = Blog::where('deleted', '!=' , 1)->where('status', '!=' , false)->where('available', true)->get()->toArray();
        }
        $articles = Article::getByBlogId($blog_id);

        return [
            'blogs' => $blogs,
            'articles' => $articles,
        ];
    }

    public function getChildren($parent_id) {
        $list = Blog::where('deleted', '!=', 1);
        $list = $list->where('parent', '=', $parent_id);
        $list = $list->select(['_id']);
        $response = $list->orderBy('updated_at', 'desc')->paginate(intval(1000));;
        $Blogs = $response->toArray();
        if (empty($Blogs['data'])) return false;
        foreach ($Blogs['data'] as $key => $_Blog) {
            $list_children = $this->getChildren($_Blog['_id']);
            if (!$list_children) continue;
            $Blogs['data'][$key]['childs'] = $list_children;
        }
        return $Blogs;
    }

    public function listBlogByIds($ids) {
        if (count($ids)) {
            $res = [];
            if (is_array($ids) && count($ids)) {
                $categories = Blog::whereIn('id', $ids)->get();
                $i = 0;
                foreach ($categories as $_Blog) {
                    $res[$i]['label'] = $_Blog->title;
                    $res[$i]['value'] = $_Blog->_id;
                    $i++;
                }
            }


            return $res;
        }
    }

    public function getPage($limit_gallery = 9, $limit_article = 6, $order_by='published') {
        $data = [];
        $gallery = Gallery::where('deleted', '!=', 1)->where('available', true)->where('status', '!=', false)->limit($limit_gallery)->get();
        $data['gallery'] = $gallery;
        $news = Blog::where('seo_alias', 'bao-chi')->first();
        if($news){
            $news_articles = Article::where('deleted', '!=', 1)->where('available', true)->where('status', '!=', false)->where('blog', $news->_id)->orderBy($order_by, 'desc')->limit($limit_article)->get();
            $data['news'] = $news_articles;
            $blog_articles = Article::where('deleted', '!=', 1)->where('available', true)->where('status', '!=', false)->where('blog', '!=', $news->_id)->orderBy($order_by, 'desc')->limit($limit_article)->get();
            $data['blog'] = $blog_articles;
        }else{
            $blog_articles = Article::where('deleted', '!=', 1)->where('status', '!=', false)->where('available', true)->orderBy($order_by, 'desc')->limit($limit_article)->get();
            $data['blog'] = $blog_articles;
        }
        return $data;
    }
}