<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Transaction extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'transactions';

    public function validateSave(){
        return true;
    }
}