<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Models\NavigationItem;

class Navigation extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'navigations';
    
    public static $ATTR = [];

    public $item = [];


    public function validateSave(){
        return true;
    }

    public function saveNavigation($data){

        $res = new Navigation();
        foreach ($data as $key => $val) {
            $res->{$key} = $val;
        }
        $items = $res->items;
        //unset($res->items);
        $res->save();
        if ($res->_id) {
            $this->saveMenuItem($items, $res->_id, $res->_id);
        }

        return $res->_id;
    }

    public function saveMenuItem($items, $root_id, $parent_id, $level = 0) {
        if (!count($items)) {
            return;
        }
        foreach ($items as $_item) {
            $_item['root_id'] = $root_id;
            $_item['parent_id'] = $parent_id;
            $_item['level'] = $level;

            if (isset($_item['items']) && count($_item['items'])) {
                $item_children  = $_item['items'];
                unset($_item['items']);
                $_id = NavigationItem::forceCreate($_item);
                $this->saveMenuItem($item_children, $root_id, $_id->_id, 1);
            } else {
                unset($_item['items']);
                NavigationItem::forceCreate($_item);
            }

        }
    }

    public function updateMenuItem($items, $root_id, $parent_id, $level = 0) {
        if (!count($items)) {
            return;
        }
        foreach ($items as $_item) {
            $_item['root_id'] = $root_id;
            $_item['parent_id'] = $parent_id;
            $_item['level'] = $level;

            if (isset($_item['items']) && count($_item['items'])) {
                $item_children  = $_item['items'];
                unset($_item['items']);
                $_id = NavigationItem::forceCreate($_item);

                $this->updateMenuItem($item_children, $root_id, $_id->_id, 1);
            } else {
                unset($_item['items']);
                NavigationItem::forceCreate($_item);
            }
        }
    }

    public function updateNavigation($data, $id){
        $res = Navigation::find($id);

        foreach ($data as $key => $val) {
            $res->{$key} = $val;
        }
        $items = $res->items;
        //unset($res->items);
        $r = $res->update();
        if ($r) {
            NavigationItem::where('root_id',  $id)->delete();
            if ($items) {
                $this->updateMenuItem($items, $id, $id, 0);
            }


            return $r;
        }
    }


    public function listNavigation($ids, $limit, $keyword = false) {

        $list = Navigation::where('deleted', '!=', 1);

        if ($keyword) {
            $list->where('title', 'regexp', '/.*'.$keyword.'/i');
        }

        $limit = $limit != '' ? $limit : 1000;

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }
        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));
        $res = $response->toArray();
        if (count($res['data'])) {
            foreach ($res['data'] as $key => $item) {
                $id = $item['_id'];
                $res['data'][$key]['items'] = $this->getNavigationItem($id);
            }
        }

        return $res;
    }

    public function listNavigationChildren($ids, $limit) {
        $list = Navigation::where('deleted', '!=', 1);
        $list = $list->where('parent', '=', '');
        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }

        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));

        $Navigations = $response->toArray();
        foreach ($Navigations['data'] as $key => $_Navigation) {
            $list_children = $this->getChildren($_Navigation['_id']);
            if (!$list_children) continue;
            $Navigations['data'][$key]['childs'] = $list_children;
        }
        return $Navigations;
    }

    public function getProductBySlug($slug) {
        $detail = Navigation::where('seo_alias', $slug)->first();
        $products = Product::where('deleted', '!=', 1)->where('Navigations', $detail->_id)->get();
        return ['detail' => $detail, 'products' => $products];
    }

    public function getChildren($parent_id) {
        $list = Navigation::where('deleted', '!=', 1);
        $list = $list->where('parent', '=', $parent_id);
        $list = $list->select(['_id']);
        $response = $list->orderBy('updated_at', 'desc')->paginate(intval(1000));;
        $Navigations = $response->toArray();
        if (empty($Navigations['data'])) return false;
        foreach ($Navigations['data'] as $key => $_Navigation) {
            $list_children = $this->getChildren($_Navigation['_id']);
            if (!$list_children) continue;
            $Navigations['data'][$key]['childs'] = $list_children;
        }
        return $Navigations;
    }

    public function listNavigationByIds($ids) {
        if (count($ids)) {
            $res = [];
            if (is_array($ids) && count($ids)) {
                $categories = Navigation::whereIn('id', $ids)->get();
                $i = 0;
                foreach ($categories as $_Navigation) {
                    $res[$i]['label'] = $_Navigation->title;
                    $res[$i]['value'] = $_Navigation->_id;
                    $i++;
                }
            }
            return $res;
        }
    }

    public function getByMenuId($menu_id) {
        $res = Navigation::where('menu_id', $menu_id)->where('deleted', '!=', 1)->first();
        if (!$res) {
            $res = Navigation::where('_id', $menu_id)->where('deleted', '!=', 1)->first();
        }
        $res->items = $this->getNavigationItem($res->_id);
        return $res;
    }

    public function getDefaultMenu() {
        $res = Navigation::where('default', "load")->where('deleted', '!=', 1)->get();
        $re = [];
        foreach ($res as $_item) {
            $_item->items = $this->getNavigationItem($_item->_id);
            $re[str_replace('-', '_', $_item->menu_id)] = $_item;
        }

        return $re;
    }

    public function getNavigationItem($root_id) {
        $items = NavigationItem::where('root_id', $root_id)->get()->toArray();
        return $this->_prepareItem($items);
    }

    public function _prepareItem($items) {
        $data = [];
        foreach ($items as $_item) {
            if ($_item['level'] > 0) {
                $data[$_item['parent_id']]['items'][] = $_item;
            } else {
                $_item['items'] = [];
                $data[$_item['_id']] = $_item;
            }
        }
        return array_values($data);
    }
}