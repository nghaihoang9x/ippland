<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class CollectionVendor extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'collection_vendor_items';

    public function validateSave(){
        return true;
    }


}