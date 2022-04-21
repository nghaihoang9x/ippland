<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class SpecsValue extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'specs_value';

    public function validateSave(){
        return true;
    }


}