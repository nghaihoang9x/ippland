<?php

namespace App\Observers;

use App\Models\Cart;

class CartObserver
{
    public function creating(Cart $cart)
    {
        while ($token = md5(date("H:i:s Y/d/m").rand())) {
            $dup_cart = Cart::where('device_token', $token)->first();
            if(!$dup_cart){
                $cart->device_token = $token;
                break;
            }
        };
        if (!property_exists($cart, 'products')) $cart->products = [];
        if (!property_exists($cart, 'user_id')) $cart->user_id = null;
    }

    public function updated(Cart $cart)
    {
        //
    }

    public function deleted(Cart $cart)
    {
        //
    }

    public function restored(Cart $cart)
    {
        //
    }

    public function forceDeleted(Cart $cart)
    {
        //
    }
}
