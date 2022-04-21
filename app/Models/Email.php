<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Email extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'emails';
    
    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    public static function saveEmail($data){
        $res = new Email();
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

}