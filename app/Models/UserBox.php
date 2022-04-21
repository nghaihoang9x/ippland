<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class UserBox extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'user_box';
    protected $fillable = ['id', 'user_id', 'products', 'box_token'];



    public function saveUserBox($data){
        $res = new UserBox();
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

    public function addItem($post, $user = false)
    {
        $product_exists = Product::where('_id', $post['product_id'])->first();
        $options = $post['options'];
        if ($product_exists) {
            if (isset($product_exists['variants'])) {
                foreach ($product_exists['variants'] as $_variant) {
                    $containsSearch = count(array_intersect($options, $_variant)) == count($options);
                    if ($containsSearch) {
                        $options['id'] = $_variant['id'];
                    }
                }
            }
            if (!isset($options['id'])) {
                return $this->sendError(true, \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Not found option id");
            }

            $product = (object)[
                'id' => $product_exists->_id,
                'collection_id' => $post['collection_id'],
                'box' => $post['box'],
                'options' => $options,
                'quantity' => intval(1)
            ];

            $currentBox = $this->getCurrentBox($post['box_token']);
            if (!$currentBox) {
                UserBox::firstOrCreate(['box_token' => $post['box_token']]);
                $currentBox = $this->getCurrentBox($post['box_token']);
            }
            if ($user && $user != 'expired' && !$currentBox->user_id){
                $currentBox->user_id = $user->_id;
                $currentBox->save();
            }
            if ($this->addNewProduct($product, $currentBox)) {
                $data = [];
                $userBox = $this->getCurrentBox($post['box_token']);

                return $userBox->arrayData($data);
                //return $this->getCurrentBox($post['box_token']);
            } else {
                return false;
            }
        }
    }

    public function getItems()
    {
        return $this->products;
    }

    public function getUserBoxItem()
    {
        $result = [];
        $products = is_array($this->products) ? $this->products : [];

        foreach ($products as $item) {
            $option_id = isset($item['options']["id"]) ? $item['options']["id"] : $item['options']["giveaway_id"];
            $giveaway = isset($item['options']["giveaway"]) ? $item['options']["giveaway"] : false;
            $product = Product::where('_id', $item['id'])
                ->where('variants.id', $option_id)
                ->first();
            $variants = null;
            if (isset($product->variants) && count($product->variants)) {
                foreach ($product->variants as $variant) {
                    if ($variant['id'] == $option_id)
                    {
                        $variants = $variant;
                    }
                }
            }

            if ($giveaway) {
                $variants['regular_price'] = 0;
                $variants['compare_price'] = 0;
            }
            if (!$product) {
                continue;
            }

            $result[] = [
                'id' => $product->_id,
                'title' => $product->title,
                //'body_html' => $product->body_html,
                'vendor' => $product->vendor,
                'seo_alias' => $product->seo_alias,
                'tags' => $product->tags,
                'variants' => $variants,
                'images' => isset($product->images[0]) ? $product->images[0] : '',
                'quantity' => $item['quantity'],
                'giveaway' => $giveaway
            ];
        }
        return $result;
    }

    public function getUserBoxPrice()
    {
        $items = $this->getUserBoxItem();
        $price = 0;

        foreach ($items as $item) {
            if (!$item['giveaway'])
                $price += $item['variants']['regular_price'] * $item['quantity'];
        }

        return $price;
    }

    public function checkSameProduct($check_product, $products){

        foreach ($products as $k => $product) {
            $option = empty(array_diff($product['options'], (array)$check_product->options));
            if ( $check_product->collection_id == $product['collection_id'] && $option ) {
                return $k;
            }
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
        if ($product->box) {
            return;
        }

        $isSame = $this->checkSameProduct($product);
        if ($isSame!==false) {
            $products = $this->products;
            if (!$fix)
                $products[$isSame]['quantity'] += $quantity;
            else
                $products[$isSame]['quantity'] = $quantity;
            $this->products = $products;
            $this->save();

            return true;
        }

        return false;
    }

    public function getProductInfo($product_id = null){
        $productModel = New Product();
        $products = $this->products;

        $result = [];
        if (!empty($products)) {

            foreach ($products as $product) {
                $product_in_UserBox = Product::find($product['id']);

                if (!$product_in_UserBox) continue;
                $options = $product['options'];

                $giveaway = isset($options["giveaway"]) ? $options["giveaway"] : false;

                unset($options["giveaway"]);
                foreach ($options as $key => $option) {
                    if ($key != 'id') continue;
                    $p_id = isset($option) ? $option : $product['options']['giveaway_id'];

                    $product_info['id'] = $product['id'];
                    $product_info['box'] = $product['box'];
                    $product_info['collection_id'] = $product['collection_id'];
                    $product_info['quantity'] = $product['quantity'];
                    $product_info['seo_alias'] = $product_in_UserBox->seo_alias;
                    $product_info['title'] = $product_in_UserBox->title;
                    $product_info['images'] = isset($product_in_UserBox->images[0]) ? $product_in_UserBox->images[0] : 0;
                    //$product_info['body_html'] = $product_in_UserBox->body_html;
                    $product_info['variants'] = $product_in_UserBox->getVariantById($p_id);
                    $product_info['collection'] = $product_in_UserBox->collections;
                    if ($giveaway) {
                        $product_info['variants']['regular_price'] = 0;
                        $product_info['variants']['compare_price'] = 0;
                        $product_info['giveaway'] = true;
                    }
                    $result[] = $product_info;
                }
                //if ($product_id !== null && $product_id == $product['id']) return $product_info;
            }
        }

        return $result;
    }

    public function getProductPrice($product_id, $variant_id){
        $product = (object) ['id' => $product_id, 'options' => ['id' => $variant_id]];
        $isSame = $this->checkSameProduct($product);
        if ($isSame === false) {
            return 0;
        }
        $product_in_UserBox = Product::find($product_id);
        $variants = $product_in_UserBox->getVariantById($variant_id);
        $products = $this->products;
        return $products[$isSame]['quantity'] * $variants['regular_price'];
    }

    public function addNewProduct($product, $currentBox) {
        $products = $currentBox->products;


        $notExistCollection = false;
        if (!empty($products)) {
            foreach ($products as $key => $_product) {
                if ($_product['collection_id'] === $product->collection_id && $_product['box'] == $product->box) {
                    $products[$key] = $product;
                    $notExistCollection = true;
                    break;
                }
            }
        }

        if (!$notExistCollection) {
            $products[] = $product;
        }
        $currentBox->products = $products;
        return $currentBox->save();
    }

    public function removeProduct($product){
        $isSame = $this->checkSameProduct($product);
        if ($product->box != '') {
            $products = $this->products;
            foreach ($products as $k => $_product) {
                if ($_product['collection_id'] == $product->collection_id) {
                    unset($products[$k]);
                }
            }
            $this->products = $products;
            $this->save();
            return true;
        }

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
        if (isset($this->products)) {
            foreach ($this->products as $product) {
                $number_item += $product['quantity'];
            }
        }

        return $number_item;
    }

    public function arrayData($data = []){
        if (!is_array($data)) $data = [];
        $userBox = $this;
        $userBox->number_item = $userBox->getNumberItems();
        $userBox->price = $userBox->getUserBoxPrice();
        $userBox->products = $userBox->getProductInfo();
        $result = $userBox->toArray();
        return empty($data) ? $result : array_intersect_key($result, array_flip($data));
    }

    public function deleteBoxItem($productID, $boxID, $boxToken, $collectionID = false)
    {
        $boxes = $this->getCurrentBox($boxToken);
        if (!$boxes) return false;

        if (isset($boxes->products) && count($boxes->products)) {
            $products = $boxes->products;
            $deleted = false;
            foreach ($products as $k => $_product) {
                if ($_product['id'] == $productID && $boxID == $_product['box'] && $collectionID == $_product['collection_id']) {
                    unset($products[$k]);
                    $deleted = true; break;
                }
            }
        }
        if ($deleted) {
            $boxes->products = array_values($products);
            $boxes->save();
            return true;
        } else {
            return false;
        }
    }

    public function clearBox($boxID, $boxToken)
    {
        $boxes = $this->getCurrentBox($boxToken);
        if (!$boxes) return false;
        $cleared = false;
        if (isset($boxes->products) && count($boxes->products)) {
            $products = $boxes->products;
            foreach ($products as $k => $_product) {
                if ($boxID == $_product['box']) {
                    unset($products[$k]);
                    $cleared = true;
                }
            }
        }

        if ($cleared) {
            $boxes->products = $products;
            $boxes->save();
            return true;
        } else {
            return false;
        }
    }

    public function addBoxToCart($boxID, $boxToken, $cartToken, $action = false)
    {
        $boxes = $this->getCurrentBox($boxToken);
        if (!$boxes) return false;

        $valid = $this->validateBeforeAddCart($boxID, $boxes);

        if (!$valid) return false;

        $productsToCart = [];
        if (isset($boxes->products) && count($boxes->products)) {
            $products = $boxes->products;

            foreach ($products as $k => $_product) {
                if ($boxID == $_product['box']) {
                    $productsToCart[] = $_product;
                }
            }
        }
        //dd($productsToCart);
        $cart_before = Cart::where('device_token', $cartToken)->first();

        if($cart_before)
        {
            if ($action == 'edit') {

                foreach ($productsToCart as $product) {
                    $cart = Cart::where('device_token', $cartToken)->first();
                    $cart->updateBoxItem((object)$product);
                }
            } else {
                foreach ($productsToCart as $product) {
                    $cart = Cart::where('device_token', $cartToken)->first();
                    $cart->addItem((object)$product);
                }
            }
        }
        Cart::where('device_token', $cartToken)->update(['box_token' => $boxToken]);
        return Cart::where('device_token', $cartToken)->first();
    }

    public function getCurrentBox($boxToken) {
        if (empty($boxToken)) return false;
        return UserBox::where('box_token', $boxToken)->first();
    }

    public function validateBeforeAddCart($boxID, $userBoxes) {
        $boxes = Box::where('seo_alias', $boxID)->first();
        $boxItems = count($boxes->collections);
        $count = 0;
        foreach ($userBoxes->products as $item) {
            if ($item['box'] != $boxID) continue;
            $count++;
        }

        if ($count != $boxItems) return false;

        return true;
    }
}
