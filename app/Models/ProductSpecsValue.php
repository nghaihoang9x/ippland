<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class ProductSpecsValue extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'product_specs_value';

    public function validateSave(){
        return true;
    }


}