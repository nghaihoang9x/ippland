<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class CollectionItem extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'collection_items';

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }
}