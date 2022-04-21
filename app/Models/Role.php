<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Role extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'roles';

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    public function saveRole($data){
        $res = new Role();
        foreach ($data as $key => $val) {
            $res->{$key} = $val;
        }
        $res->save();
        return $res->_id;
    }

    public function updateRole($data, $id){
        $res = Role::find($id);
        foreach ($data as $key => $val) {
            $res->{$key} = $val;
        }
        return $res->update();
    }

    public function listRole($ids, $limit, $keyword = false) {
        $list = Role::where('deleted', '!=', 1);
        if ($keyword) {
            $list->where('name', 'regexp', '/.*'.$keyword.'/i');
        }

        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }
        $response = $list->orderBy('updated_at')->paginate(intval($limit));

        return $response->toArray();
    }

    public function listRoleByIds($ids) {
        if (count($ids)) {
            $res = [];
            if (is_array($ids) && count($ids)) {
                $categories = Role::whereIn('id', $ids)->get();
                $i = 0;
                foreach ($categories as $_Role) {
                    $res[$i]['label'] = $_Role->title;
                    $res[$i]['value'] = $_Role->_id;
                    $i++;
                }
            }


            return $res;
        }
    }

    public function hasAccess(array $permissions) : bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission))
                return true;
        }
        return false;
    }

    private function hasPermission(string $permission) : bool
    {
        $this->permissions = json_decode($this->permissions, true);
        return $this->permissions[$permission] ?? false;
    }
}