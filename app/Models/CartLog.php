<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class CartLog extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'cart_log';

}
