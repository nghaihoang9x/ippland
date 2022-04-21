<?php

namespace App\Http\Controllers\Api;

use App\Events\StatusOrderChanged;
use App\Helpers\Referral;
use App\Models\Cart;
use App\Models\Email;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\CartLog;
use App\Models\User;
use App\Models\Staff;
use App\Models\Discount;
use App\Models\Variant;
use App\Models\OrderItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use MongoDB\BSON\ObjectID;
use Illuminate\Support\Facades\Hash;

class OrderController extends ApiController
{
    public function _prepareOrderData($order)
    {
        $objectID = new ObjectID();
        $products = $order['carts']['products'];
        $product_item = $line_items = [];

        foreach ($products as $_item) {

            $product_item["product_title"]      = $_item['title'];
            $product_item["variant_title"]      = $_item['variants']['title'];
            $product_item["product_id"]         = $_item['id'];
            $product_item["price"]              = $_item['variants']['regular_price'];

            $product_item["quantity"]           = $_item['quantity'];
            $product_item["total_price"]        = $_item['variants']['regular_price'] * $_item['quantity'];
            $product_item["variant_id"]         = $_item['variants']['_id'];
            $product_item["sku"]                = $_item['variants']['sku'];
            $product_item["weight"]             = $_item['variants']['weight'];
            $product_item["length"]             = $_item['variants']['length'];
            $product_item["width"]              = $_item['variants']['width'];
            $product_item["height"]             = $_item['variants']['height'];
            $product_item["image"]              = isset($_item['images']) && isset($_item['images']['src']) ? $_item['images']['src'] : '';

            $order_item = $product_item;
            $order_item['order_id'] = $objectID;
            OrderItem::forceCreate($order_item);

            $line_items[] = $product_item;
        }
        $shipping_price = isset($order['carts']["shipping_price"]) ? $order['carts']["shipping_price"] : 0;
        $surcharge_price = isset($order['carts']["surcharge_price"]) ? $order['carts']["surcharge_price"] : 0;
        $subtotal_price    = isset($order['carts']["price"]) ? $order['carts']["price"] : 0;
        $subtotal_price    += isset($order['carts']["box_price"]) ? $order['carts']["box_price"] : 0;

        if (isset($order['carts']["total_discounts"]))
            $total_price       = $subtotal_price - $order['carts']["total_discounts"];
        else
            $total_price       = $subtotal_price;

        $total_price += $shipping_price;
        $total_price += $surcharge_price;

        $billing_address = [
            'fullname'=> isset($order['options']['full_name']) ? $order['options']['full_name'] : '',
            'phone'=> isset($order['options']['phone']) ? $order['options']['phone'] : '',
            'city'=> isset($order['options']['city_display']) ? $order['options']['city_display'] : '',
            'ward'=> isset($order['options']['ward_display']) ? $order['options']['ward_display'] : '',
            'district'=> isset($order['options']['district_display']) ? $order['options']['district_display'] : '',
            'country'=> 'Việt Nam',
            'address'=> isset($order['options']['address']) ? $order['options']['address'] : '',
            'country_code'=> 'VN',
            'city_code'=> isset($order['options']['city']) ? $order['options']['city'] : '',
            'district_code'=> isset($order['options']['district']) ? $order['options']['district'] : '',
            'ward_code'=> isset($order['options']['ward']) ? $order['options']['ward'] : '',
            'as_shipping_address' => isset($order['options']['as_shipping_address']) ? $order['options']['as_shipping_address'] : '',
        ];

        if(!isset($order['options']['as_shipping_address']) || (isset($order['options']['as_shipping_address']) && intval($order['options']['as_shipping_address']) != 1)){
            $shipping_address = $billing_address;
        }else{
            $shipping_address = [
                'fullname'=> isset($order['options']['billing_name']) ? $order['options']['billing_name'] : '',
                'phone'=> isset($order['options']['billing_phone']) ? $order['options']['billing_phone'] : '',
                'city'=> isset($order['options']['billing_city_display']) ? $order['options']['billing_city_display'] : '',
                'ward'=> isset($order['options']['billing_ward_display']) ? $order['options']['billing_ward_display'] : '',
                'district'=> isset($order['options']['billing_district_display']) ? $order['options']['billing_district_display'] : '',
                'country'=> 'Việt Nam',
                'address'=> isset($order['options']['billing_address']) ? $order['options']['billing_address'] : '',
                'country_code'=> 'VN',
                'city_code'=> isset($order['options']['billing_city']) ? $order['options']['billing_city'] : '',
                'district_code'=> isset($order['options']['billing_district']) ? $order['options']['billing_district'] : '',
                'ward_code'=> isset($order['options']['billing_ward']) ? $order['options']['billing_ward'] : ''
            ];
        }

        $user = User::where('email', $order['options']['email'])->first();
        if (!$user) {
            $objectUserID = new ObjectID();
            $orderData['account_pass'] = $this->generateOrder();
            $user = User::firstOrCreate([
                '_id' => (string) $objectUserID,
                'id' => (string) $objectUserID,
                'password' => Hash::make($orderData['account_pass']),
                'activation_code' => hash_hmac('sha256', str_random(40), $orderData['account_pass']),
                'email' => $order['options']['email'],
                'phone' => $order['options']['phone'],
                'fullname' => $order['options']['full_name'],
                'shipping_address' => $shipping_address,
                'from' => 'order',
                'is_active' => false,
                'orders_count' => 1,
                'total_spent' => $total_price,
                'address' => $order['options']['address']
            ]);

        } else {

            $user_order_count = Order::where('user_id', $user->_id)->count();
            $user_total_spent = Order::where('user_id', $user->_id)->sum('total_price');

            User::where('_id', '=', $user->_id)->update([
                'shipping_address' => $shipping_address,
                'fullname' => $order['options']['full_name'],
                'orders_count' => intval($user_order_count + 1),
                'total_spent' => $user_total_spent + $total_price
            ]);

            User::where('_id', '=', $user->_id)->update(['address' => $order['options']['address']]);
        }

        $customer = [
            'id' => $user->_id,
            'email' => $user->email,
            'fullname' => $user->fullname,
            'phone' => $user->phone,
            'total_spent' => 0,
            'orders_count' => 0
        ];

        $orderData = [
            '_id' => (string) $objectID,
            'id' => (string) $objectID,
            'title'=> $order['title'],
            'order_status'=> 'pending',
            'subtotal_price'=> $subtotal_price,
            'total_price'=> $total_price,
            'total_discount'=> isset($order['carts']["total_discounts"]) ? $order['carts']["total_discounts"] : 0,
            'total_shipping'=> $shipping_price,
            'total_surcharge'=> $surcharge_price,
            'discount_code'=> isset($order['carts']["discount_code"]) ? $order['carts']["discount_code"] : 0,
            'discount_amount'=> '',
            'discount_type'=> '',
            'customer'=> $customer,
            'shipping_address'=> $shipping_address,
            'billing_address'=> $billing_address,
            'shipping_method'=> '',
            'shipping_status'=> 'incomplete',
            'shipping_carrier'=> 'viettelpost',
            'shipping_tracking_code'=> '',
            'payment_method'=> isset($order['options']['payment_method']) ? strtoupper($order['options']['payment_method']) : '',
            'payment_bank' => isset($order['options']['bankcode']) && strtoupper($order['options']['bankcode']) != 'VISA' && strtoupper($order['options']['bankcode']) != 'MASTERCARD' ? $order['options']['bankcode'] : '',
            'payment_card' => isset($order['options']['bankcode']) && (strtoupper($order['options']['bankcode']) == 'VISA' || strtoupper($order['options']['bankcode']) == 'MASTERCARD') ? strtoupper($order['options']['bankcode']) : '',
            'payment_status'=> 'incomplete',
            'shipping_updated_at'=> '',
            'refunded_at'=> '',
            'cancelled_at'=> '',
            'cancel_reason'=> '',
            'closed_at'=> '',
            'shipped_at'=> '',
            'confirmed_at'=> '',
            'paid_at'=> '',
            'line_items' => $line_items,
            'cnote'=> isset($order['options']['cnote']) ? $order['options']['cnote'] : '',
            'user_id' => $user->_id,
            'note'=> '',
            'tags'=> [],
            'locale'=>  isset($order['options']['locale']) ? strtolower($order['options']['locale']) : '',
            'bill_export' => isset($order['options']['bill_export']) && $order['options']['bill_export'] == 1 ? true : false,
            'bill_type' => isset($order['options']['bill_type']) ? $order['options']['bill_type'] : '',
            'billing_company' => isset($order['options']['billing_company']) ? $order['options']['billing_company'] : '',
            'billing_tax_code' => isset($order['options']['billing_tax_code']) ? $order['options']['billing_tax_code'] : '',
            'billing_full_address' => isset($order['options']['billing_full_address']) ? $order['options']['billing_full_address'] : '',
            'billing_person_email' => isset($order['options']['billing_person_email']) ? $order['options']['billing_person_email'] : '',
        ];

        return $orderData;
    }

    public function updateQuantity($products)
    {

        foreach ($products as $_item) {
            $variant_id = isset($_item['variants']['_id']) ? $_item['variants']['_id'] : '';
            $variant = Variant::find($variant_id);
            if($variant){
                $variant->hold_quantity = intval(isset($variant->hold_quantity) ? $variant->hold_quantity + 1 : 1);
                $variant->update();
            }
//            $product = Product::find($_item['id']);
//            $variants = $product['variants'];
//            $variants[0]['hold_quantity'] = isset($variants[0]['hold_quantity']) ? $variants[0]['hold_quantity'] + 1 : 1;
//            $product['variants'] = $variants;
//            $product->update();
        }
    }

    public function saveOrder()
    {
        $modelOrder = new Order();
        $post = request()->all();

        $order_number = $this->generateOrder();
        if (Order::where('name', $order_number)->first()) {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error save order");
        }
        $post['title'] = $order_number."-".date('Hidm');

        // check discount code
        $discount_code = isset($post['carts']["discount_code"]) ? $post['carts']["discount_code"] : "";
        if ($discount_code) {
            $discount = Discount::where('deleted', '!=', '1')->where('title', 'like', "$discount_code")->first();

            if (!$discount) return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Can not use the discount code.");

            if ($discount->usage_limit != "" && $discount->used >= $discount->usage_limit)
                return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Can not use the discount code.");;
            $discount->used = $discount->used + 1;
            $discount->save();
        }

        if (isset($post['is_admin']) && $post['is_admin']) {
            $res = Order::forceCreate($post);

            $res['response_time'] = microtime(true) - LARAVEL_START;
            if ($res) {
                $transaction['order_id'] = $res->_id;
                $transaction['shipping_status'] = $res->shipping_status;
                $transaction['payment_status'] = $res->payment_status;
                $transaction['description'] = 'Đơn hàng được tạo';

                Transaction::forceCreate($transaction);
            }
            //$this->updateQuantity($post['carts']['products']);

            return $this->responseSuccess($res);
        } else {
            $cart = Cart::where(['device_token' => $post['device_token']])->first();
            $data = [];
            if ($cart && $post['device_token'] != "") {

                $res = $cart->arrayData($data);

                $products = isset($res['products']) ? $res['products'] : [];
            }

            //$user = User::checkToken();
            //if ($user == 'expired') return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Vui lòng đăng nhập lại");


            $post['carts']['products'] = $products;
            $orderData = $this->_prepareOrderData($post);
            //return $this->responseSuccess($orderData);

            //if ($user && $user != 'expired'){
            //$post['user_id'] = $user->_id;
            $res = Order::forceCreate($orderData);
            //dd($res->_id);

            if ($res) {
                $orderData['response_time'] = microtime(true) - LARAVEL_START;
                $orderData['_id'] = $res->_id;
                $transaction['order_id'] = $res->_id;
                $transaction['shipping_status'] = $res->shipping_status;
                $transaction['payment_status'] = $res->payment_status;

                $transaction['description'] = 'Đơn hàng được tạo';
                Transaction::forceCreate($transaction);
                $modelOrder->updateHoldInventory($res->line_items, $res->location_id);
                if ($orderData['payment_method'] == 'COD') {
                    Cart::where('device_token', $post['device_token'])->delete();
                    event(new StatusOrderChanged($res, 'created'));
                }

//                if (isset($post['carts']['box_token']))
//                    UserBox::where('box_token', $post['carts']['box_token'])->delete();

                $post['order_id'] = $res->_id;
                CartLog::forceCreate($post);


                // Thêm tạm thời cho camp promotion
                $add_gift = false;
                $_giftable_list = ['Jillian Edelweiss', 'Fall in Lust', 'Fall in Lust Limited by David Chieze', 'Just a Desire', 'Drop of Love', 'Hotting up Sweetly', 'Dark Fiction',
                    "I'm Yours", 'Bewitching Kiss', 'Honey and Bee', 'Blooming Garden', 'Passion in Love'];
                if (isset($res->line_items)) {
                    foreach ($res->line_items as $_item) {
                        if (in_array($_item['product_title'], $_giftable_list) && in_array($_item['variant_title'], ['50ml', '60ml'])) {
                            $add_gift = true;
                            break;
                        }
                    }
                }
                if($add_gift){
                    Transaction::forceCreate(['order_id' => $res->_id, 'type' => 'note', 'description' => 'Tặng set vial 2ml']);
                }
            }
//            $this->updateQuantity($post['carts']['products']);
            //$this->email($post['options']['email'], 'create_order');

            //Log cart

            return $this->responseSuccess($orderData);
            //}
        }

        return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error save order");
    }

    public function email($email, $action)
    {
        $data['to'] = $email;
        $data['action'] = $action;
        $data['status'] = 0;
        Email::saveEmail($data);
    }

    public function generateOrder()
    {
        //$today = date("Ym");
        $rand = substr(hexdec(md5(microtime())), 2, 4);
        return $unique = $rand;
    }

    public function getOrder(Request $request) {

        $get = $request->all();
        $get['query'] = 'list';
        if (count($get) == 0 || isset($request['admin'])) {
            $staffs = Staff::checkToken();
            if ($staffs == false || $staffs == 'expired') {
                return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Vui lòng đăng nhập lại");
            }
            unset($get['admin']);
        }

        $OrderModel = new Order();

        $result = $OrderModel->listOrders($get);

        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function updateOrder(Request $request)
    {
        $user = User::checkToken();

        /*if (!$user || $user == 'expired'){
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Vui lòng đăng nhập lại");
        }*/

        $OrderModel = new Order();
        $get = $request->all();
        $order_code = $request->get('order_code');
        $order_id = $request->get('order_id');
        $nl_token = $request->get('nl_token');
        $success = $request->get('success');
        $transid = $request->get('transid');
        $payment_status = isset($get['payment_status']) ? $get['payment_status'] : '';

        $device_token = $request->get('device_token');
        if ($order_id) {
            $result = $OrderModel->updateOrder($order_id, false, false, $nl_token, $success, $device_token, $payment_status, true, $transid);
        } else {
            $result = $OrderModel->updateOrder($order_code, false, false, $nl_token, $success, $device_token, $payment_status);
        }

        if ($result) {

            return $this->responseSuccess($result);
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function delete(Request $request)
    {
        $ids = $request->get('ids');

        $OrderModel = new Order();
        if ($ids) {
            $ids = explode(',', $ids);
            foreach ($ids as $_id) {
                $data['deleted'] = 1;
                $OrderModel->updateOrder($data, $_id);
            }
            $result['deleted_ids'] = $ids;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function update(Request $request, $id)
    {
        $data = [];
        $post = $request->all();
        foreach ($post as $key => $value) {
            $data[$key] = $value;
        }

        $OrderModel = new Order();
        $result = $OrderModel->updateOrder($data, $id);
        if ($result) {
            if(isset($result['success']) && $result['success'] == false){
                return $this->responseSuccess($result);
            }else {
                $res['response_time'] = microtime(true) - LARAVEL_START;
                $res['success'] = true;
                $res['request'] = $request->all();
                $res['request']['note'] = '';
                $res['request']['transactions'] = $OrderModel->getTransactions($id);
                return $this->responseSuccess($res);
            }
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Order Not Found");
        }
    }

    public function search(Request $request)
    {
        $get = $request->all();

        $OrderModel = new Order();
        $result = $OrderModel->listOrders($get);
        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
    }

    public function filter(Request $request)
    {


        // return array_keys($request->all());
        // $limit = $request->get('limit');
        // $order_status = $request->get('order_status');
        // $shipping_status = $request->get('shipping_status');
        // $payment_status = $request->get('payment_status');
        // $coupon_code = $request->get('coupon_code');
        // $from = $request->get('from');
        // $to = $request->get('to');

        $OrderModel = new Order();
        $result = $OrderModel->filter($request->all());
        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        } else {
            $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
        }
    }

    public function copy($order_id){
        $order = Order::find($order_id);
        if($order){
            $objectID = new ObjectID();
            $new_order = $order->replicate();
            $new_order->_id = (string) $objectID;
            $new_order->id = (string) $objectID;
            $new_order->shipping_method = '';
            $new_order->shipping_status = 'incomplete';
            $new_order->shipping_tracking_code = 'incomplete';
            $new_order->payment_status = 'incomplete';
            $new_order->shipping_updated_at = '';
            $new_order->refunded_at = '';
            $new_order->cancelled_at = '';
            $new_order->cancel_reason = '';
            $new_order->closed_at = '';
            $new_order->shipped_at = '';
            $new_order->confirmed_at = '';
            $new_order->paid_at = '';
            $new_order->note = '';
            $new_order->order_status = 'pending';
            $res = $new_order->push();
            $res['success'] = $res;
            return $this->responseSuccess($res);
        }
    }
}
