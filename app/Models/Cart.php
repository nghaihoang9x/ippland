<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Cart extends Eloquent {

    protected $connection = 'mongodb';
    protected $Cart = 'carts';
    protected $fillable = ['id', 'user_id', 'products', 'device_token'];



    public function saveCart($data){
        $res = new Cart();
        foreach ($data as $key => $val) {
            $res->{$key} = $val;
        }

        $res->save();
        return $res->_id;
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function addItem($item, $quantity = 1)
    {
        return $this->checkSameProduct($item)!==false ?
            $this->changeProductQuantity($item, $quantity) : $this->addNewProduct($item, $quantity);

    }

    public function getItems()
    {
        return $this->products;
    }

    public function getCartItem()
    {
        $result = [];
        $products = is_array($this->products) ? $this->products : [];

        foreach ($products as $item) {
            $variant_id = isset($item['variant_id']) ? $item['variant_id'] : '';
            $variants_ = Variant::where(function ($q) use ($variant_id){
                $q->orWhere('_id', $variant_id)
                    ->orWhere('id', $variant_id)
                    ->orWhere('variant_id', $variant_id);
            })->get()->toArray();
            $variants = isset($variants_[0]) ? $variants_[0] : [];
            $product = Product::where('_id', $item['id'])
                ->first();

            if (empty($product->_id)) {
                $this->removeProduct($item);
                continue;
            }
            if($product->images && isset($variants_['image']) && !empty($variants_['image'])){
                foreach($product->images as $image){
                    if($image->id == $variants->image){
                        $images = $image;
                        break;
                    }
                }
            }elseif(isset($product->images[0])){
                $images = $product->images[0];
            }else{
                $images = [];
            }
            $result[] = [
                'id' => $product->_id,
                'title' => $product->title,
                //'body_html' => $product->body_html,
                'vendor' => $product->vendor,
                'seo_alias' => $product->seo_alias,
                'tags' => $product->tags,
                'variants' => $variants,
                'featured_image' => isset($product->featured_image) ? $product->featured_image : '',
                'images' => $images,
                'quantity' => $item['quantity'],
            ];
        }

//        dd($result);
        return $result;
    }

    public function getCartPrice()
    {
        $items = $this->getCartItem();
        $price = 0;

        foreach ($items as $item) {
            if (isset($item['variants']['regular_price']))
                $price += $item['variants']['regular_price'] * $item['quantity'];
        }

        return $price;
    }

    public function checkSameProduct($check_product){
        if(is_array($check_product))
            $check_product = (object) $check_product;
        foreach ($this->getItems() as $k => $product) {
            if (isset($product['id']) && $check_product->id == $product['id'] && $check_product->variant_id == $product['variant_id'])

                return $k;
        }

        return false;
    }

    public function checkGiveAwayProduct($check_product){
        foreach ($this->getItems() as $k => $product) {
            if (isset($product['giveaway_pid']) && $check_product->id == $product['giveaway_pid'])
                return $k;
        }

        return false;
    }

    public function getProductQuantity($product){
        $isSame = $this->checkSameProduct($product);
        if ($isSame===false) return 0;
        $products = $this->products;

        return $products[$isSame]['quantity'];
    }

    public function changeProductQuantity($product, $quantity = 1, $fix = false){
        $isSame = $this->checkSameProduct($product);
        if ($isSame!==false) {
            $products = $this->products;

            if (!$fix)
                $products[$isSame]['quantity'] += (isset($product->quantity) ? $product->quantity : $quantity);
            else
                $products[$isSame]['quantity'] = (isset($product->quantity) ? $product->quantity : $quantity);

            $this->products = $products;
            $this->save();

            return true;
        }

        return false;
    }

    public function getProductInfo($product_id = null){
        $products = $this->products;
        $result = [];
        foreach ($products as $product) {
            if (!Product::where('_id', $product['id'])->count() || !Variant::where(function($query) use ($product){
                    $query->orWhere('_id', $product['variant_id'])
                        ->orWhere('id', $product['variant_id'])
                        ->orWhere('variant_id', $product['variant_id']);
                })->count()) {
                continue;
            }

            $product_in_cart = Product::find($product['id'])->toArray() ;
            $variants = Variant::find($product['variant_id'])->toArray();
            if (!$product_in_cart) continue;
            $product_info = $product_in_cart;
            $product_info['variants'] =  $variants;
            $product_info['quantity'] = $product['quantity'];
            if($product_in_cart['images'] && isset($variants['image']) && !empty($variants['image'])){
                foreach($product_in_cart['images'] as $image){
                    if($image['id'] == $variants['image']){
                        $product_info['images'] = $image;
                    }
                }
            }elseif(isset($product_in_cart['images'][0])){
                $product_info['images'] = $product_in_cart['images'][0];
            }else{
                $product_info['images'] = [];
            }
            $result[] = $product_info;
            //if ($product_id !== null && $product_id == $product['id']) return $product_info;
        }
        return $result;
    }

    public function getProductPrice($product_id, $variant_id){
        $product = (object) ['id' => $product_id, 'variant_id' => $variant_id];
        $isSame = $this->checkSameProduct($product);
        if ($isSame === false) {
            return 0;
        }
        $variants = Variant::find($variant_id);
        $products = $this->products;

        return $products[$isSame]['quantity'] * $variants->regular_price;
    }

    public function addNewProduct($product, $quantity = 1){
        $isSame = $this->checkSameProduct($product);
        if ($isSame) {
            return false;
        }
        $products = $this->products;
        $product->quantity = (isset($product->quantity) ? $product->quantity : $quantity);
        $products[] = $product;
        $this->products = $products;
        $this->save();

        return true;
    }

    public function removeProduct($product){
        $isSame = $this->checkSameProduct($product);

        if ($isSame === false) return false;
        $products = $this->products;
        unset($products[$isSame]);

        $this->products = $products;
        $this->save();

        return true;
    }

    public function getNumberItems()
    {
        $number_item = 0;
        foreach ($this->products as $product) {
            $number_item += $product['quantity'];
        }
        return $number_item;
    }

    public function arrayData($data = []){

        if (!is_array($data)) $data = [];
        $cart = $this;
        $cart->number_item = $cart->getNumberItems();
        $cart->price = $cart->getCartPrice();
        $cart->products = $cart->getProductInfo();
        $result = $cart->toArray();
        return empty($data) ? $result : array_intersect_key($result, array_flip($data));
    }

    public static function mergeCart($cartUser, $cartToken) {
        if ($cartToken->user_id == $cartUser->user_id) return;

        $products = $cartToken->getItems();
        foreach ($products as $key => $product) {
            $prod = (object) ['id' => $product['id'], 'variant_id' => $product['variant_id'], 'quantity' => intval($product['quantity'])];
            $cartUser->addItem($prod);
        }
        $cartToken->delete();
    }
}
