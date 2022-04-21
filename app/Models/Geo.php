<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Geo extends Eloquent
{

    protected $connection = 'mongodb';
    protected $collection = 'geos';

    /**
     * @param string $slug
     * @return mixed
     */
    public static function getCityBySlug($slug = '')
    {
        $city = self::where('slug', $slug)->first();

        return $city;
    }

    /**
     * @param string $code
     * @return mixed
     */
    public static function getCityByCode($code = '')
    {
        $city = self::where('code', $code)->first();

        return $city;
    }

    public static function getDistrictByCode($data = [])
    {
        $districts = self::whereIn('code', $data)->get();

        return $districts;
    }
}