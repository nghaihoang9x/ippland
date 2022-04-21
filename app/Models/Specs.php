<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Specs extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'specs';

    public function validateSave(){
        return true;
    }


}