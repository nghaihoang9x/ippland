<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class ProjectServiceValue extends Eloquent {

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'project_service_values';

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }
}
