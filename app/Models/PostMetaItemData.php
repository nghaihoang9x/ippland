<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class PostMetaItemData extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'post_meta_item_data';

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

}
