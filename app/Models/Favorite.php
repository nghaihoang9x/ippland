<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;

class Favorite extends Eloquent
{

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'favorites';
    protected $fillable = ['type', 'user_id', 'favorite_value', 'deleted'];

    /**
     * @param $data
     * @return mixed
     */
    public static function getFavorites($data)
    {
        $list = Favorite::where('deleted', '!=', 1)
            ->where('user_id', $data['user_id'])
            ->where('type', 'product')
            ->orderBy('updated_at', 'desc');

        return $list->get();
    }

    /**
     * @param $data
     * @return mixed
     */
    public static function saveFavorite($data)
    {
        $user_id = isset($data['user_id']) ? $data['user_id'] : '';
        $favorite_value = isset($data['id']) ? $data['id'] : '';
        $type = isset($data['type']) ? $data['type'] : '';

        $favorite = self::where('user_id', $user_id)
            ->where('type', $type)
            ->where('favorite_value', $favorite_value)
            ->first();

        if ($favorite) {
            return $favorite;
        }

        $favorite = self::create([
            'type' => $type,
            'favorite_value' => $favorite_value,
            'user_id' => $user_id
        ]);

        return $favorite;
    }

    public static function deleteFavorite($user_id, $id)
    {
        $favorite = Favorite::where('user_id', $user_id)
            ->where('favorite_value', $id)
            ->delete();
//        $favorite->update([
//            'deleted' => 1
//        ]);

        return $favorite;
    }
}