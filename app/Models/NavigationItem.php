<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class NavigationItem extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'navigation_items';

    public function validateSave(){
        return true;
    }


}