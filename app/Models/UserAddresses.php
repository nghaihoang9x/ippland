<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class UserAddresses extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'user_addresses';
    protected $fillable = ["_token","full_name","mobile","city","district","wards","city_display","district_display","wards_display","address","default","user_id"];

    public function validateSave(){
        return true;
    }

    public function addAddress() {

    }

    public function updateAddress() {

    }
}