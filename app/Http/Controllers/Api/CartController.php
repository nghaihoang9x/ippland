<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\Discount;
use App\Models\Setting;
use App\Models\User;
use App\Models\Product;
use App\Http\Controllers\Api\ApiController;
use App\Models\Variant;
use App\Models\Zone;

class CartController extends ApiController
{
    private $discount_fail_message = '';

    public function store()
    {
        $res = ['success' => false, 'message' => 'Có lỗi! Vui lòng thử lại sau'];
        $post = request()->all();
        $user = User::checkToken();
        $product_exists = Product::where('_id',$post['product_id'])->first();
        $variant_id = isset($post['variant_id']) ? $post['variant_id'] : '';
        $current_variant = Variant::where(function ($q) use ($variant_id){
            $q->orWhere('_id', $variant_id)
                ->orWhere('id', $variant_id)
                ->orWhere('variant_id', $variant_id);
        })->first();
        $qty = 0;
        if ($product_exists){
            $cart_check = Cart::where('device_token', $post['device_token'])->first();

            if ($cart_check && count($cart_check->products)) {
                foreach ($cart_check->products as $_product) {
                    if (isset($_product['id']) && $_product['id'] == $post['product_id']) {
                        $qty = $_product['quantity'];
                    }
                }
            }
            if ($qty > 0 && $qty >= $current_variant->quantity && env('ALLOW_OVERBOOK') !== true) {
                return $this->responseSuccess(['success' => false, 'message' => 'Tất cả số lượng sản phẩm này đã nằm trong giỏ hàng của bạn.']);
                //return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Tất cả số lượng sản phẩm này đã nằm trong giỏ hàng của bạn.");
            }

            $product = (object) ['id' => $product_exists->_id, 'variant_id' => $post['variant_id'], 'quantity' => intval($post['quantity'])];

            if ($user && $user != 'expired'){
                $result = $user->addToCart($product);
                if ($result) {
                    if ($user->cart->discount_code) {
                        $this->discount($user->cart->discount_code);
                    }
                    $res = [];
                    $res['success'] = true;
                    $res['message'] = 'Sản phẩm đã được thêm vào giỏ hàng';
                    $res['cart_items'] = $user->cart->getProductInfo();
                    $res['number_cart_item'] = $user->cart->getNumberItems();
                    $res['response_time'] = microtime(true) - LARAVEL_START;

                } else {
                    $cart = Cart::where('device_token', $post['device_token'])->first();
                    $cart->user_id = $user->_id;
                    $cart->save();
                    $cart->addItem($product);

                    $cart = Cart::where('device_token', $post['device_token'])->first();
                    if ($cart->discount_code) {
                        $this->discount($cart->discount_code);
                    }
                    $res = [];
                    $res['success'] = true;
                    $res['message'] = 'Sản phẩm đã được thêm vào giỏ hàng';
                    $res['cart_items'] = $cart->getProductInfo();
                    $res['number_cart_item'] = $cart->getNumberItems();
                    $res['response_time'] = microtime(true) - LARAVEL_START;

                }
            }
            else
            if($cart = Cart::where('device_token', $post['device_token'])->first())
            {
                $cart->addItem($product);
                $cart = Cart::where('device_token', $post['device_token'])->first();
                if ($cart->discount_code) {
                    $this->discount($cart->discount_code);
                }
                $cart = Cart::where('device_token', $post['device_token'])->first();
                $res = [];
                $res['success'] = true;
                $res['message'] = 'Sản phẩm đã được thêm vào giỏ hàng';
                $res['cart_items'] = $cart->getProductInfo();
                $res['number_cart_item'] = $cart->getNumberItems();
                $res['response_time'] = microtime(true) - LARAVEL_START;
                return $this->responseSuccess($res);

            }

            return $this->responseSuccess($res);
        }

        if ($user == 'expired') {
            return $this->responseSuccess(['success' => false, 'message' => 'Vui lòng đăng nhập lại']);
//            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Vui lòng đăng nhập lại");
        }
        return $this->responseSuccess(['success' => false, 'message' => 'Không thể thêm sản phẩm vào giỏ hàng']);
//        return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error Add to cart");
    }

    public function show($device_token, $data = '')
    {
        $data = $data != '' ? explode(',', $data) : [];
        $this->responseSuccess($data);
        $user = User::checkToken();
        if ($user && $user != 'expired'){
            return $this->responseSuccess($user->cart->arrayData($data));
        }
        $cart = Cart::where(['device_token' => $device_token])->first();
        if ($cart && $device_token != "")
            return $this->responseSuccess($cart->arrayData($data));

        return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Có lỗi xảy ra");
    }

    public function discount($code = false) {
        $post = request()->all();
        $data = [];
        $cart = Cart::where(['device_token' => $post['device_token']])->first();
        $user = User::checkToken();
        $data = $cart->arrayData($data);
        $code = $code ? $code : $post['code'];

        if ($code) {
            $discount = Discount::where('deleted', '!=', '1')->where('title', 'like', "$code")->first();
            if($discount) {
                if (
                    //check end date < current time
                    (!empty($discount->ends_at) && strtotime($discount->ends_at) < time())
                    ||
                    //check start date > current time
                    (!empty($discount->starts_at) && strtotime($discount->starts_at) > time())
                    ||
                    //check limit used
                    ($discount->usage_limit != "" && $discount->used >= $discount->usage_limit)
                ) {
                    $msg_ = "Mã giảm giá đã hết hạn hoặc chưa đến hạn sử dụng";
                    return $this->sendError("Not found", \Illuminate\Http\Response::HTTP_BAD_REQUEST, $msg_);
                }
            }else{
                $msg_ = "Mã giảm giá không tồn tại";
                return $this->sendError("Not found", \Illuminate\Http\Response::HTTP_BAD_REQUEST, $msg_);
            }
        }

        if (isset($post['clear']) && $post['clear']) {
            if ($user && $user != 'expired') {
                $res = Cart::where('user_id', '=', $user->_id)->update(['discount_code' => '', 'total_discounts' => 0]);
            } else{
                $res = Cart::where('device_token', '=', $post['device_token'])->update(['discount_code' => '', 'total_discounts' => 0]);
            }

            return $this->responseSuccess($res);
        }

        $_total_price   = $this->get_cart_total_price($data);

        $total_discount = $this->applyDiscount($code, $data, $_total_price);

        if ($total_discount < 0){
            if ($user && $user != 'expired') {
                $cartUpdate = Cart::where(['user_id' => $user->_id])->first();
            } else{
                $cartUpdate = Cart::where(['device_token' => $post['device_token']])->first();
            }

            $cartUpdate->total_discounts = 0;
            $cartUpdate->discount_code = '';
            $cartUpdate->update();
            return $this->sendError("Not found", \Illuminate\Http\Response::HTTP_BAD_REQUEST, $this->discount_fail_message);
        }

//        if ($total_discount == 0) {
//            return $this->sendError("Not found", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Không tồn tại mã giảm giá");
//        }
        if ($user && $user != 'expired') {
            $cartUpdate = Cart::where(['user_id' => $user->_id])->first();
        } else {
            $cartUpdate = Cart::where(['device_token' => $post['device_token']])->first();
        }
        $cartUpdate->total_discounts = $total_discount;
        $cartUpdate->discount_code = $code;
        return $this->responseSuccess($cartUpdate->update());
    }

    private function get_cart_total_price ($cart){
        $price = 0;

        if (isset($cart['products']) && count($cart['products']) > 0){
            foreach ($cart['products'] as $item){

                $price += intval($item['variants']['regular_price']) * $item['quantity'];

            }

        }
        return (int) $price;
    }

    public function update()
    {
        $post = request()->all();
        $user = User::checkToken();
        $product_exists = Product::where('_id', $post['product_id'])->first();
        $variant_id = $post['variant_id'];
        $variant_exists = Variant::where(function ($q) use ($variant_id){
            $q->orWhere('_id', $variant_id)
                ->orWhere('id', $variant_id)
                ->orWhere('variant_id', $variant_id);
        })->first();

        $qty = $post['quantity'];
        if ($qty > $variant_exists->quantity && env('ALLOW_OVERBOOK') !== true) {
            return $this->responseSuccess(['success' => false, 'message' => 'Tất cả số lượng sản phẩm này đã nằm trong giỏ hàng của bạn.']);
//            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Tất cả số lượng sản phẩm này đã nằm trong giỏ hàng của bạn.");
        }
        if ($variant_exists){
            $product = (object) ['id' => $product_exists->_id, 'variant_id' => $post['variant_id']];
            if ($user && $user != 'expired'){
                $result = $user->cart->changeProductQuantity($product, intval($post['quantity']), true);

                $user = User::checkToken();
                if ($result) {
                    if ($user->cart->discount_code) {
                        $this->discount($user->cart->discount_code);
                    }
                    $res = $user->cart->toArray();
                    $res['success'] = true;
                    $res['price'] = $user->cart->getProductPrice($product_exists->_id, $post['variant_id']);
                    $res['total_price'] = $user->cart->getCartPrice();
                    $res['number_cart_item'] = $user->cart->getNumberItems();
                    $res['response_time'] = microtime(true) - LARAVEL_START;

                    return $this->responseSuccess($res);
                }
            }
            else
                if($cart = Cart::where('device_token', $post['device_token'])->first())
            {
                if($cart->products){

                }
                $cart->changeProductQuantity($product, intval($post['quantity']), true);
                if ($cart->discount_code) {
                    $this->discount($cart->discount_code);
                }
                $cart = Cart::where('device_token', $post['device_token'])->first();
                $res = $cart->toArray();
                $res['success'] = true;
                $res['price'] = $cart->getProductPrice($product_exists->_id, $post['variant_id']);
                $res['total_price'] = $cart->getCartPrice();
                $res['number_cart_item'] = $cart->getNumberItems();
                $res['response_time'] = microtime(true) - LARAVEL_START;

                return $this->responseSuccess($res);
            }
        }

        if ($user == 'expired'){
            return $this->responseSuccess(['success' => false, 'message' => 'Vui lòng đăng nhập lại']);
//            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Vui lòng đăng nhập lại");
        }
        return $this->responseSuccess(['success' => false, 'message' => 'Không thể thêm sản phẩm vào giỏ hàng']);
//        return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error Add to cart");
    }

    public function delete()
    {
        $post = request()->all();
        $user = User::checkToken();

        $product_exists = Product::where('_id',$post['product_id'])->first();
        if ($product_exists){
            $product = (object) ['id' => $product_exists->_id, 'variant_id' => $post['variants']];
            if ($user && $user != 'expired'){
                $result = $user->cart->removeProduct($product);
                $user = User::checkToken();
                if ($result) {
                    if ($user->cart->discount_code) {
                        $this->discount($user->cart->discount_code);
                    }
                    $res = $user->cart->toArray();
                    $res['success'] = true;
                    $res['cart_items'] = $user->cart->getProductInfo();
                    $res['total_price'] = $user->cart->getCartPrice();
                    $res['number_cart_item'] = $user->cart->getNumberItems();
                    $res['response_time'] = microtime(true) - LARAVEL_START;

                    return $this->responseSuccess($res);
                }
            }
            else
                if($cart = Cart::where('device_token', $post['device_token'])->first())
            {

                $cart->removeProduct($product);
                if ($cart->discount_code) {
                    $this->discount($cart->discount_code);
                }
                $cart = Cart::where('device_token', $post['device_token'])->first();
                $res = $cart->toArray();
                $res['success'] = true;
                $res['cart_items'] = $cart->getProductInfo();
                $res['total_price'] = $cart->getCartPrice();
                $res['number_cart_item'] = $cart->getNumberItems();
                $res['response_time'] = microtime(true) - LARAVEL_START;

                return $this->responseSuccess($res);
            }
        }
    }

    public function clearDiscount($code, $order) {
        $discount = Discount::where('title', 'like', "$code")->first();
        if (!$discount) return 0;

        //if (strtotime($discount->ends_at) > strtotime(date('Y-m-d')) || $discount->used >= $discount->usage_limit) return 0;
        if ($discount->used >= $discount->usage_limit) return 0;
        $discount = Discount::where('title', 'like', "$code")->orderBy('created_at', 'desc')->first();
        if ($discount->apply_to == 'order'){
            return $discount->discount_value;
        }

        $total_discount = 0;
        foreach ($order['products'] as $_product) {
            $total_discount += $this->getDiscount($discount, $_product);
        }

        return $total_discount;
    }

    public function applyDiscount($code, $order, $_total_price) {
        $total_discount_apply = 0;
        $discount = Discount::where('title', 'like', "$code")->first();

        if (isset($discount->minimum_type) && $discount->minimum_type == 'minimum_amount' && isset($discount->minimum_amount) && $discount->minimum_amount && $_total_price <= $discount->minimum_amount){
            $this->discount_fail_message   = "Mã giảm giá này chỉ sử dụng cho đơn hàng trị giá trên " . number_format($discount->minimum_amount,0, ',', '.') . ' đ' ;
            return -1;
        }

        if (isset($discount->minimum_type) && $discount->minimum_type == 'minimum_quantity' && isset($discount->minimum_quantity) && $discount->minimum_quantity && $order['number_item'] >= $discount->minimum_quantity){
            $this->discount_fail_message   = "Mã giảm giá này chỉ sử dụng cho đơn hàng có tổng " . number_format($discount->minimum_quantity,0, ',', '.') . ' sản phẩm trở lên' ;
            return -1;
        }

        if (!$discount) return 0;
        //if (strtotime($discount->ends_at) > strtotime(date('Y-m-d')) || $discount->usage_limit != "" && $discount->used >= $discount->usage_limit) return 0;
        if ( $discount->usage_limit != "" && $discount->used >= $discount->usage_limit) return 0;

        if ($discount->apply_to == 'order'){
//            $this->updateDiscountUsage($discount, 1);
            $total = 0;
            foreach ($order['products'] as $product) {
                $total += $product['quantity'] * $product['variants']['regular_price'];
            }

            if ($discount->discount_type == 'percentage') {
                $total_discount_apply = $discount->discount_value > 0 ? $total*($discount->discount_value/100) : 0;
            }else{
                $total_discount_apply = $discount->discount_value;
            }

            //apply to collections
        }elseif($discount->apply_to == 'entitled_collection_ids'){
            if(isset($discount->entitled_collection_ids) && $discount->entitled_collection_ids){
                $total = 0;
                foreach ($discount->entitled_collection_ids as $collection){
                    foreach ($order['products'] as $product) {
                        if(in_array($collection, $product['collections'])) {
                            $total += $product['quantity'] * $product['variants']['regular_price'];
                        }
                    }
                }
                if ($discount->discount_type == 'percentage') {
                    $total_discount_apply = $discount->discount_value > 0 ? $total * ($discount->discount_value / 100) : 0;
                } else {
                    $total_discount_apply = $discount->discount_value;
                }
                if($total == 0)
                {
                    $this->discount_fail_message   = "Mã giảm giá không áp dụng cho nhóm sản phẩm này";
                    return -1;
                }
            }

            //apply to products
        }elseif($discount->apply_to == 'entitled_product_ids'){
            if(isset($discount->entitled_product_ids) && $discount->entitled_product_ids){
                $total = 0;
                foreach ($order['products'] as $product) {
                    if(in_array($product['id'], $discount->entitled_product_ids)) {
                        $total += $product['quantity'] * $product['variants']['regular_price'];
                    }
                }

                if ($discount->discount_type == 'percentage') {
                    $total_discount_apply = $discount->discount_value > 0 ? $total * ($discount->discount_value / 100) : 0;
                } else {
                    $total_discount_apply = $discount->discount_value;
                }

                if($total == 0){
                    $this->discount_fail_message   = "Mã giảm giá không áp dụng cho sản phẩm này";
                    return -1;
                }

            }

            /* $discount->variants = [
             *  `product_id` => [
             *      `sku_id`
             *  ]
             * ]
             * */
        }elseif($discount->apply_to == 'entitled_variant_ids'){
            if(isset($discount->entitled_variant_ids) && $discount->entitled_variant_ids){
                $total = 0;
                foreach ($order['products'] as $product) {
                    if(is_array($discount->entitled_variant_ids) && in_array($product['variants']['_id'], $discount->entitled_variant_ids)) {
                        $total += $product['quantity'] * $product['variants']['regular_price'];
                    }
                }

                if ($discount->discount_type == 'percentage') {
                    $total_discount_apply = $discount->discount_value > 0 ? $total * ($discount->discount_value / 100) : 0;
                } else {
                    $total_discount_apply = $discount->discount_value;
                }

                if($total == 0){
                    $this->discount_fail_message   = "Mã giảm giá không áp dụng cho sản phẩm này";
                    return -1;
                }

            }

            /* $discount->tags = [
             *  `tag_name`
             * ]
             * */
        }elseif($discount->apply_to == 'entitled_tag_ids'){
            if(isset($discount->entitled_tag_ids) && $discount->entitled_tag_ids){
                $total = 0;
                foreach ($order['products'] as $product) {
                    if(isset($product['tags']) && !empty($product['tags']) && is_array($product['tags'])){
                        foreach ($discount->entitled_tag_ids as $discount_tag){
                            if(in_array($discount_tag, $product['tags'])) {
                                $total += $product['quantity'] * $product['variants']['regular_price'];
                            }
                        }
                    }
                }

                if ($discount->discount_type == 'percentage') {
                    $total_discount_apply = $discount->discount_value > 0 ? $total * ($discount->discount_value / 100) : 0;
                } else {
                    $total_discount_apply = $discount->discount_value;
                }

                if($total == 0){
                    $this->discount_fail_message   = "Mã giảm giá không áp dụng cho sản phẩm này";
                    return -1;
                }

            }
        }else {

            $total_discount = 0;
            foreach ($order['products'] as $_product) {
                $total_discount += $this->getDiscount($discount, $_product);
            }

            $total_discount_apply =  $total_discount;
        }

        if (isset($discount->maximum_type) && $discount->maximum_type == 'maximum_amount' && isset($discount->maximum_amount) && $discount->maximum_amount && $total_discount_apply > $discount->maximum_amount){
            $total_discount_apply = $discount->maximum_amount;
        }

        return $total_discount_apply;
    }

    public function getDiscount($discount, $product){
        if($this->isCouponAvailable($discount->title, $product)){
            $this->updateDiscountUsage($discount, $product['quantity']);
            if ($discount->discount_type == 'fixed_amount'){
                return $product['quantity'] * ($product['variants']['regular_price'] - ($product['variants']['regular_price'] - $discount->discount_value)) ;
            } else if ($discount->discount_type == 'percentage') {
                return $product['quantity'] * $product['variants']['regular_price']*($discount->discount_value/100);
            }
        }
        return 0;
    }

    public function updateDiscountUsage($discount, $number){
        $discount->used = $discount->used + $number;
        $discount->save();
    }

    public function isCouponAvailable($code, $product){
        $discount = Discount::where('title', $code)->first();
        /*if ($discount->apply_to == 'order'){
            return true;
        }*/

        if ($discount->apply_to == 'entitled_product_ids') {
            if (in_array($product['_id'], $discount->entitled_product_ids)) {
                return true;
            } else {
                return false;
            }
        }
        //dd($product);
        if ($discount->apply_to == 'entitled_collection_ids') {
            if (in_array($product['collections'][0], $discount->entitled_collection_ids)) {
                return true;
            } else {
                return false;
            }
        }

        if ($discount->apply_to == 'entitled_variant_ids') {
            if (in_array($product['variants']['_id'], $discount->entitled_variant_ids)) {
                return true;
            } else {
                return false;
            }
        }

        if ($discount->apply_to == 'entitled_tag_ids') {
            if (in_array($product['tags'], $discount->entitled_tag_ids)) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    public function shipping(){
        $user = User::checkToken();
        $post = request()->all();
        $city = isset($post['city']) ? $post['city'] : '';
        $device_token = isset($post['device_token']) ? $post['device_token'] : '';

        $settingModel = new Setting();
        $setting = $settingModel->general();

        if(isset($setting->ship_for) && $setting->ship_for == 'for_price'){
            if ($user && $user != 'expired') {
                $cart = Cart::where('user_id', '=', $user->_id)->first();
            }else{
                $cart = Cart::where('device_token', '=', $device_token)->first();
            }
            $cart_total = $cart->getCartPrice();
            $shipping_fee = 0;
            if($cart && isset($setting->max_order_price) && intval($cart_total) - intval($cart->total_discounts) <= intval($setting->max_order_price)){
                $shipping_fee = isset($setting->shipping_fee_default) ? $setting->shipping_fee_default : 0;
            }
        }else {
            $shipping = Zone::where('ship_to_cities', '=', $city)->where('deleted', '!=', 1)->first();
            $shipping_fee = isset($shipping->base_rates) ? intval($shipping->base_rates) : (isset($setting->shipping_fee) && $city ? $setting->shipping_fee : 0);
        }

        if ($user && $user != 'expired') {
            Cart::where('user_id', '=', $user->_id)->update(['shipping_price' => $shipping_fee]);
            $res = Cart::where('user_id', '=', $user->_id)->first();
        } else{
            Cart::where('device_token', '=', $device_token)->update(['shipping_price' => $shipping_fee]);
            $res = Cart::where('device_token', '=', $device_token)->first();
        }
        $res->success = true;
        return $this->responseSuccess($res->arrayData([]));
    }

    public function surcharge(){
        $post = request()->all();
        $payment_method = isset($post['payment_method']) ? $post['payment_method'] : '';
        $device_token = isset($post['device_token']) ? $post['device_token'] : '';
        $settingModel = new Setting();
        $setting = $settingModel->general();
        $surcharge = isset($setting->surcharge) && $payment_method == 'COD' ? $setting->surcharge : 0;

        $user = User::checkToken();
        if ($user && $user != 'expired') {
            $cart = Cart::where('user_id', '=', $user->_id)->first();
            $total_price = $cart->getCartPrice();
            $cart->surcharge_price = round($total_price * $surcharge / 100);
            $cart->save();
            $res = Cart::where('user_id', '=', $user->_id)->first();
        } else{
            $cart = Cart::where('device_token', '=', $device_token)->first();
            $total_price = $cart->getCartPrice();
            $cart->surcharge_price = round($total_price * $surcharge / 100);
            $cart->save();
            $res = Cart::where('device_token', '=', $device_token)->first();
        }
        $res->success = true;
        return $this->responseSuccess($res->arrayData([]));
    }
}
