<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Auth\User as Authenticatable;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use DateTime;

class User extends Authenticatable
{
    use Notifiable;
    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = ['email', 'password', 'phone', 'fullname', 'locale', 'address'];

    protected $hidden = ['password'];

    protected static function boot()
    {
        parent::boot();
        self::saving(function ($model) {
            $model->email = strtolower($model->email);
        });
    }

    public static function checkToken()
    {
        try {
            JWTAuth::parseToken();
            $token = JWTAuth::getToken();
            $user = JWTAuth::toUser($token);

            return $user;
        } catch (TokenExpiredException $e) {
            return 'expired';
        } catch (JWTException $e) {
            return false;
        } catch (\Exceptions $e) {
            return false;
        }
    }

    public static function createUser($credentials)
    {
        unset($credentials['password_confirmation']);
        $user = self::forceCreate($credentials);
        $user->id = $user->_id;
        $user->email = strtolower($credentials['email']);
        $user->password = Hash::make($credentials['password']);
        $user->activation_code = hash_hmac('sha256', str_random(40), $credentials['password']);
        $user->pass = isset($credentials['password']) ? $credentials['password'] : '';

        return $user->save() ? $user : false;
    }

    public static function updateUser($data)
    {
        $res = User::find($data['user_id']);
        if (!empty($data['password']) &&
            !empty($data['old_password']) && !empty($data['password_confirmation'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            $data['password'] = $res->password;
        }

        if (isset($data['fullname'])) {
            $data['fullname'] = $data['fullname'];
        }
        if (isset($data['phone'])) {
            $data['phone'] = $data['phone'];
        }

        unset($data['user_id']);
        unset($data['old_password']);
        unset($data['password_confirmation']);

        foreach ($data as $key => $val) {

            $res->{$key} = $val;
        }
        return $res->update();
    }

    public function cart()
    {
        return $this->hasOne('App\Models\Cart');
    }

    public function reviews()
    {
        return $this->hasMany('App\Models\Review');
    }

    public function addresses()
    {
        return $this->hasMany('App\Models\UserAddresses');
    }

    public function addToCart($item)
    {
        $cart = Cart::where('user_id', $this->_id)->first();

        if ($cart) {
            return $cart->addItem($item);
        }
    }

    public function createCart()
    {
        if ($this->cart == null) {
            return $this->cart()->save(new Cart);
        }

        return $this->cart;
    }

    public function addCart($device_token)
    {
        $thisCart = $this->createCart();
        $cart = Cart::where('device_token', $device_token)->first();
        if ($cart) {
            $products = $cart->getItems();
            foreach ($products as $key => $product) {
                $prod = (object)['id' => $product['id'], 'options' => $product['options'], 'quantity' => intval($product['quantity'])];
                $thisCart->addItem($prod);
            }
        }
        return true;
    }

    public function getAddresses()
    {
        return UserAddresses::where('user_id', $this->_id)->get();
    }

    public function addAddresses($address)
    {
        return UserAddresses::forceCreate($address);
    }

    public function active()
    {
        $this->activation_code = null;

        return $this->save();
    }

    public function getOrders($limit)
    {
        $limit = $limit != '' ? $limit : env('PAGINATION');
        //Order::where('user_id', $this->_id)->where('type', 'draft')->delete();
        return Order::where('user_id', $this->_id)->where('type', '!=', 'draft')->select(['title', 'line_items', 'created_at', 'total_price', 'order_status'])->orderBy('updated_at', 'desc')->paginate(intval($limit));
    }

    public function getOrder($id)
    {
        //Order::where('user_id', $this->_id)->where('type', 'draft')->delete();
        return Order::where('user_id', $this->_id)->where('type', '!=', 'draft')->where('title', '=', $id)
            ->orderBy('updated_at', 'desc')->first();
    }

    public static function activeCode($code)
    {
        $user = self::where('activation_code', $code)->first();
        if ($user) {
            $user->activate = true;
            $user->save();

            return $user;
        }

        return false;
    }

}
