<?php

namespace App\Models;

use App\Events\PaymentStatusOrderChanged;
use App\Events\ShippingStatusOrderChanged;
use App\Events\StatusOrderChanged;
use App\Helpers\Common;
use Illuminate\Support\Carbon;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use MongoDB\BSON\ObjectID;

class Order extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'orders';
    protected $dates = ['deleted_at'];
    protected $fillable = ['title', 'images', 'seo_alias', 'body_html', 'products', 'seo_title', 'seo_description', 'view_count', 'seo_title', 'seo_description'];

    public function validateSave(){
        return true;
    }

    protected static function boot()
    {
        parent::boot();
        self::saving(function ($model) {
            if(isset($model->transactions))
                unset($model->transactions);
            if(isset($model->customer_orders))
                unset($model->customer_orders);
            if(isset($model->staff))
                unset($model->staff);
        });

        self::updated(function ($model) {
            if(isset($model->line_items)){
                foreach ($model->line_items as $item){
                    $order_item = OrderItem::where(function ($query) use($model){
                        $query->orWhere('order_id', (string)$model->_id)
                        ->orWhere('order_id', new ObjectID($model->_id));
                    })->count();
                    if($order_item <= 0) {
                        $res = new OrderItem();
                        foreach ($item as $key => $val) {
                            $res->{$key} = $val;
                        }
                        $res->order_id = $model->_id;
                        $res->save();
                    }
                }
            }
        });
    }

    public function listOrders($request) {
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $order_code = isset($request['order_code']) ? $request['order_code'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $start = isset($request['start_date']) ? $request['start_date'] : '';
        $stop = isset($request['end_date']) ? $request['end_date'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        $type = isset($request['type']) ? $request['type'] : 'created_at';
        $export = isset($request['export']) ? $request['export'] : '';

        $query = isset($request['query']) ? $request['query'] : 'search';
        $discounts_code = isset($request['discounts_code']) ? $request['discounts_code'] : '';
        $work_shift = isset($request['work_shift']) ? $request['work_shift'] : '';

        if ($ids) {
            $ids = explode(',', $ids);
        }
        //Order::where('type', '=', 'draft')->delete();
        if(is_array($ids) && count($ids)){
            $list = Order::where('deleted', '!=', 1);
        }
        elseif($type == 'draft'){
            $list = Order::where('deleted', '!=', 1)->where('type', '=', 'draft');
            $type = 'created_at';
        }else{
            $list = Order::where('deleted', '!=', 1)->where('type', '!=', 'draft');
        }

        if(!$ids && $query == 'list') {
            $list = $list->select(['payment_method', 'payment_select', 'user_id', 'payment_cart', 'payment_bank', 'payment_status',
                'shipping_method', 'shipping_status', 'shipping_shipper', 'shipping_tracking_code',
                'shipping_price', 'title', 'order_status', 'subtotal_price', 'total_price', 'total_discounts',
                'discounts_code', 'total_shipping', 'customer', 'type', 'box_token', 'updated_at', 'created_at',
                'id', 'tags', 'total_surcharge', 'shipping_address']);
        }
        if ($order_code) {
            return Order::where('order_code', '=', $order_code)->first();
        }

        if ($keyword) {
            $list->orWhere('title', 'regexp', '/.*'.$keyword.'/i');
            $list->orWhere('customer.phone', 'regexp', '/.*'.$keyword.'/i');
            $list->orWhere('customer.email', 'regexp', '/.*'.$keyword.'/i');
            $list->orWhere('customer.fullname', 'regexp', '/.*'.$keyword.'/i');
        }

        if($discounts_code){
            $list->where('discounts_code', 'like', "{$discounts_code}");
        }

        if($request){
            unset($request['options']);
            unset($request['device_token']);
            unset($request['carts']);
            foreach ($request as $k => $v){
                if(!in_array($k, ['ids', 'limit', 'keyword', 'start_date', 'end_date', 'page', 'type', 'options', 'device_token', 'carts', 'query', 'discounts_code', 'plan_id', 'work_shift', 'buyer_type', 'api_token', 'export'])){
                    if($v == 'false' || $v == 'true')
                        $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                    $list->where($k, $v);
                }
            }
        }
        if ($start && $stop) {
            $sdt = Carbon::createFromFormat('Y-m-d', $start);
            $edt = Carbon::createFromFormat('Y-m-d', $stop);
            //export for work shift
            if($start == $stop && ($work_shift && $work_shift != 'all')){
                //for morning work shift
                if($work_shift == 'am'){
                    $start = date('Y-m-d', strtotime('-1 day', strtotime($start)));
                    $sdt = Carbon::createFromFormat('Y-m-d', $start);
                    $list = $list->where(function ($query) use ($type, $sdt, $start){
                        $query->where($type, '>=', Carbon::create($sdt->year, $sdt->month, $sdt->day, 17, 0, 0))
                            ->orWhere($type, '>=', $start.' 17:00:00');
                    });

                    $list = $list->where(function ($query) use ($type, $edt, $stop){
                        $query->where($type, '<=', Carbon::create($edt->year, $edt->month, $edt->day, 9, 59, 59))
                            ->orWhere($type, '<=', $stop.' 09:59:59')
                            ->orWhere($type, '<=', $stop);
                    });

                    //for afternoon work shift
                }elseif($work_shift == 'pm'){
                    $list = $list->where(function ($query) use ($type, $sdt, $start){
                        $query->where($type, '>=', Carbon::create($sdt->year, $sdt->month, $sdt->day, 10, 0, 0))
                            ->orWhere($type, '>=', $start.' 10:00:00');
                    });

                    $list = $list->where(function ($query) use ($type, $edt, $stop){
                        $query->where($type, '<=', Carbon::create($edt->year, $edt->month, $edt->day, 16, 59, 59))
                            ->orWhere($type, '<=', $stop.' 16:59:59');
                    });
                }
            }else{

                $list = $list->where(function ($query) use ($type, $sdt, $start){
                    $query->where($type, '>', Carbon::create($sdt->year, $sdt->month, $sdt->day, 0, 0, 0))
                        ->orWhere($type, '>', $start.' 00:00:00')
                        ->orWhere($type, '>', $start);
                });

                $list = $list->where(function ($query) use ($type, $edt, $stop){
                    $query->where($type, '<', Carbon::create($edt->year, $edt->month, $edt->day, 23, 58, 0))
                        ->orWhere($type, '<', $stop.' 23:59:59')
                        ->orWhere($type, '<', $stop);
                });
            }
            $list = $list->orderBy($type, 'desc');
            $data = $list->get()->toArray();
            $array_data  = [];
            if($export && $data){
                foreach($data as $k => $v){
                    if(isset($v['line_items']) && $v['line_items']){
                        $total_quantity = 0;
                        foreach($v['line_items'] as $item){
                            $total_quantity += intval($item['quantity']);
                        }
                        foreach($v['line_items'] as $k => $item){
                            $new_item = $v;
                            $new_item['total_quantity'] = $total_quantity;
                            if($k > 0){
                                foreach ($v as $k1 => $v1){
                                    $new_item[$k1] = '';
                                }
                                $new_item['_id'] = $v['_id'];
                                $new_item['title'] = $v['title'];
                                $new_item['order_status'] = $v['order_status'];
                                $new_item['payment_method'] = $v['payment_method'];
                                $new_item['payment_status'] = $v['payment_status'];
                                $new_item['shipping_status'] = $v['shipping_status'];
                                $new_item['total_quantity'] = '';
                                $new_item['shipping_address'] = [
                                    'address' => '',
                                    'city' => '',
                                    'district' => '',
                                    'ward' => '',
                                ];
                            }
                            $new_item['line_items'] = $item;
                            $array_data[] = $new_item;
                        }
                    }
                }
            }
            return ['success' => true, 'data' => $array_data];
        }

        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }
        $response = $list->orderBy($type, 'desc')->paginate(intval($limit));

        $res = $response->toArray();

        if (count($res['data'])) {
            foreach ($res['data'] as $key => $item) {
                $id = $item['_id'];
                $res['data'][$key]['note'] = '';
                $res['data'][$key]['cnote'] = isset($item['cnote']) ? $item['cnote'] : '';
                $res['data'][$key]['line_items'] = isset($item['line_items']) ? $this->getProductItem($item['line_items']) : '';
                if (is_array($ids) && count($ids))
                    $res['data'][$key]['tags'] = isset($item['tags']) ? $item['tags'] : [];
                else
                    $res['data'][$key]['tags'] = isset($item['tags']) ? Tag::getTags($item['tags']) : [];
                if($ids || $query != 'list') {
                    $res['data'][$key]['transactions'] = $this->getTransactions($id);
                    if(isset($item['user_id']))
                    $res['data'][$key]['customer_orders'] = $this->getAllOrderByCustomer($item['user_id']);
                    if(isset($item['user_id'])) {
                        $customer_ = Customer::find($item['user_id']);
                        $res['data'][$key]['customer']['orders_count'] = $customer_->orders_count;
                        if(isset($customer_->orders_count))
                            $res['data'][$key]['customer']['orders_count'] = $customer_->orders_count;
                        if(isset($customer_->total_spent))
                            $res['data'][$key]['customer']['total_spent'] = $customer_->total_spent;
                    }
                }
            }
        }

        return $res;

    }

    public function getAllOrderByCustomer($id){
        $total_order = Order::where('user_id', $id)->where('type', '!=', 'draft')->get(['title', 'is_alternative', 'line_items', 'title', 'order_status', 'plan_id', 'total_price', 'payment_status']);
        return $total_order;
    }

    public function getTransactions($id) {
        $return = [];
        $transactions = Transaction::where('order_id', '=', $id)->orderBy('created_at', 'desc')->get(['created_at', 'description', 'type', 'by']);

        if($transactions){
            foreach ($transactions as $k => $transaction){
                if(!isset($transaction->type))
                    $transaction->type = '';
                if(!isset($transaction->by))
                    $transaction->by = 'Hệ thống';
                $return[$k] = $transaction;
                unset($return[$k]['_id']);
            }
        }
        return $return;
    }

    public function getProductItem($line_items){
        $res = [];
        if($line_items){
            foreach ($line_items as $k => $item){
                $variant_id = $item['variant_id'];
                $variant = Variant::where(function ($q) use ($variant_id){
                    $q->orWhere('_id', $variant_id)
                        ->orWhere('id', $variant_id)
                        ->orWhere('variant_id', $variant_id);
                })->first();
                $variant->quantity = $item['quantity'];
                $variant->image = $item['image'];
                $variant->product_title = $item['product_title'];
                $variant->total_price = $item['total_price'];
                $variant->price = isset($item['price']) ? $item['price'] : $variant['regular_price'];
                $res[$k] = $variant;
            }
        }
        return $res;
    }

    public function updateOrder($order_code, $id = false, $user_id = false, $nl_token = false, $success = false, $device_token = false, $payment_status = '', $by_id = false, $transid = '') {
        if ($id) {
            $res = Order::find($id);
            $warehouse_defaut = Warehouse::where('deleted', '!=', 1)->first();
            if (isset($order_code['deleted'])) {
                $res->deleted = 1;
                return $res->update();
            }


            $old_shipping_status = $res->shipping_status;
            $old_payment_status = $res->payment_status;
            $old_status = $res->order_status;
            $notes = isset($order_code['note']) ? $order_code['note'] : "";

            foreach ($order_code as $key => $val) {
                $res->{$key} = $val;
            }
            if(!isset($res->location_id)){
                $res->location_id = isset($warehouse_defaut->_id) ? $warehouse_defaut->_id : '';
            }



            //save transaction when shipping status change
            if (isset($order_code['shipping_status']) && $old_shipping_status != $order_code['shipping_status']) {
                $transaction['order_id'] = $id;
                $transaction['shipping_status'] = $order_code['shipping_status'];
                $transaction['type'] = 'order_update';
                $transaction['description'] = 'Trạng thái vận chuyển thay đổi từ ' . Common::shipping_status($old_shipping_status) . ' thành ' . Common::shipping_status($order_code['shipping_status']);
                if(isset($staff_) && !empty($staff_)){
                    $transaction['by'] = (isset($staff_['name']) ? $staff_['name'] : '').(isset($staff_['email']) ? " ({$staff_['email']})" : '');
                }
                Transaction::forceCreate($transaction);
                if($order_code['shipping_status'] == 'shipping'){
                    event(new ShippingStatusOrderChanged($res, 'shipping'));
                }elseif($order_code['shipping_status'] == 'completed'){
                    event(new ShippingStatusOrderChanged($res, 'completed'));
                }
            }

            //save transaction when payment status change
            if (isset($order_code['payment_status']) && $old_payment_status != $order_code['payment_status']) {
                $transaction['order_id'] = $id;
                $transaction['payment_status'] = $order_code['payment_status'];
                $transaction['type'] = 'order_update';
                $transaction['description'] = 'Trạng thái thanh toán chuyển thay đổi từ ' . Common::payment_status($old_payment_status) . ' thành ' . Common::payment_status($order_code['payment_status']);
                if(isset($staff_) && !empty($staff_)){
                    $transaction['by'] = (isset($staff_['name']) ? $staff_['name'] : '').(isset($staff_['email']) ? " ({$staff_['email']})" : '');
                }
                Transaction::forceCreate($transaction);
                if($order_code['payment_status'] == 'completed'){
                    $res->email_status = 'payment_success';
                    event(new PaymentStatusOrderChanged($res));
                }elseif($order_code['payment_status'] == 'refunded' && $order_code['order_status'] != 'canceled'){
                    event(new StatusOrderChanged($res, 'refunded'));
                    //$this->updateInventory($res->line_items, $res->location_id, true);
                }
            }

            //save transaction when add notes
            if ($notes) {
                $transaction['order_id'] = $id;
                $transaction['description'] = $notes;
                $transaction['type'] = 'note';
                if(isset($staff_) && !empty($staff_)){
                    $transaction['by'] = (isset($staff_['name']) ? $staff_['name'] : '').(isset($staff_['email']) ? " ({$staff_['email']})" : '');
                }
                Transaction::forceCreate($transaction);
            }

            //save transaction when order status change
            if (isset($order_code['order_status']) && $old_status != $order_code['order_status']) {
                $transaction['order_id'] = $id;
                $transaction['description'] = 'Trạng thái đơn hàng thay đổi từ '. Common::order_status($old_status) .' thành ' . Common::order_status($order_code['order_status']);
                if(isset($staff_) && !empty($staff_)){
                    $transaction['by'] = (isset($staff_['name']) ? $staff_['name'] : '').(isset($staff_['email']) ? " ({$staff_['email']})" : '');
                }
                $transaction['type'] = 'order_update';
                Transaction::forceCreate($transaction);

                if($order_code['order_status'] == 'confirmed'){
                    $res_inventory = $this->updateInventory($res->line_items, $res->location_id);
                    if(isset($res_inventory['success']) && $res_inventory['success'] == false){
                        return $res_inventory;
                    }
                    event(new StatusOrderChanged($res, 'confirmed'));
                }elseif($order_code['order_status'] == 'canceled' && $order_code['payment_status'] != 'refunded'){
                    if($order_code['order_status'] == 'canceled'){
                        event(new StatusOrderChanged($res, 'canceled'));
                    }
                    $this->updateInventory($res->line_items, $res->location_id, true);
                }
            }

            $res->payment_transaction_id = $transid;

            return $res->update();
        }


        if ($order_code && $success) {

            if ($by_id){
                $order = Order::where('_id', '=', $order_code)->first();

            }else{
                $order = Order::where('title', '=', $order_code)->first();
            }

//            if ($order->nl_token == $nl_token) {

                Cart::where('device_token', $device_token)->delete();

                //save transaction
                $transaction['order_id'] = $order->_id;
                $transaction['payment_status'] = 'completed';
                $transaction['type'] = 'order_update';
                $transaction['description'] = 'Trạng thái thanh toán chuyển thay đổi từ ' . Common::payment_status('incomplete') . ' thành ' . Common::payment_status('completed');

                Transaction::forceCreate($transaction);

                //send mail
//                $res_inventory = $this->updateInventory($order->line_items, $order->location_id);
//                if(isset($res_inventory['success']) && $res_inventory['success'] == false){
//                    return $res_inventory;
//                }
//                event(new StatusOrderChanged($order, 'confirmed'));
                $order->email_status = 'payment_completed';
                event(new PaymentStatusOrderChanged($order));
                if ($by_id) {

                    return Order::where('_id', '=', $order_code)->update(['payment_status' => $payment_status ? $payment_status : 'completed', 'type' => 'ordered']);
                }else{
                    return Order::where('title', '=', $order_code)->update(['payment_status' => $payment_status ? $payment_status : 'completed', 'type' => 'ordered']);
                }
//            }
        }

        if ($order_code && $nl_token) {
            if ($by_id) {

                return Order::where('_id', '=', $order_code)->update(['nl_token' => $nl_token]);
            }else{
                return Order::where('title', '=', $order_code)->update(['nl_token' => $nl_token]);
            }
        }




        return false;
    }

    public function updateInventory($line_items, $location_id, $addition = false)
    {

        if($line_items){
            foreach ($line_items as $k => $item){
                $variant_id = $item['variant_id'];
                //update variant
                $variant = Variant::where(function ($q) use ($variant_id){
                    $q->orWhere('_id', $variant_id)
                        ->orWhere('id', $variant_id)
                        ->orWhere('variant_id', $variant_id);
                })->first();

                //update inventory
                $warehouse = Warehouse::find($location_id);
                $inventory = Inventory::where('variant_id', $variant->_id)->where('warehouse_id', $location_id)->first();

                if(isset($inventory->_id) && (($inventory->quantity >= $item['quantity'] && !$addition && env('ALLOW_OVERBOOK') !== true) || $addition)){
                    //update variant
                    $variant->quantity = $addition ? intval($variant->quantity + $item['quantity']) : intval($variant->quantity - $item['quantity']);
                    $variant->hold_quantity = isset($variant->hold_quantity) ?
                        ($addition ? intval($variant->hold_quantity - $item['quantity']) : intval($variant->hold_quantity))
                        : 0;

                    $variant->update();

                    $inventory->quantity = $addition ? intval($inventory->quantity + $item['quantity']) : intval($inventory->quantity - $item['quantity']);
                    $inventory->hold_quantity = isset($inventory->hold_quantity) ?
                        ($addition ? intval($inventory->hold_quantity - $item['quantity']) : intval($inventory->hold_quantity))
                        :
                        0;
                    $inventory->save();
                }else{
                    return ['success' => false, 'message' => (isset($warehouse->name) ? $warehouse->name : '')." không đủ số lượng sản phẩm ".(isset($item['product_title']) ? $item['product_title'] : '').(isset($variant->title) ? " ({$variant->title})" : '')];
                }
            }
        }
    }

    public function updateHoldInventory($line_items, $location_id, $addition = true)
    {
        if($line_items){
            foreach ($line_items as $k => $item){
                $variant_id = $item['variant_id'];
                $variant = Variant::where(function ($q) use ($variant_id){
                    $q->orWhere('_id', $variant_id)
                        ->orWhere('id', $variant_id)
                        ->orWhere('variant_id', $variant_id);
                })->first();

                //update inventory
                $inventory = Inventory::where('variant_id', $variant->_id)->where('warehouse_id', $location_id)->first();
                if(isset($inventory->_id)){
                    //update variant
                    $variant->hold_quantity =
                        isset($variant->hold_quantity)
                            ?
                            ($addition ? intval($variant->hold_quantity + $item['quantity']) : intval($variant->hold_quantity - $item['quantity']))
                            :
                            ($addition ? intval($item['quantity']) : 0);
                    $variant->update();

                    $inventory->hold_quantity = isset($variant->hold_quantity) ?
                        ($addition ? intval($variant->hold_quantity + $item['quantity']) : intval($variant->hold_quantity - $item['quantity']))
                        :
                        ($addition ? intval($item['quantity']) : 0);
                    $inventory->save();
                }
            }
        }
    }

    public function updateOrder1($order_code, $id = false, $user_id = false, $nl_token = false, $success = false) {
        if ($id) {
            $res = Order::find($id);
            $old_shipping_status = $res->shipping_status;
            $old_payment_status = $res->payment_status;
            $old_status = $res->order_status;
            $notes = isset($order_code['note']) ? $order_code['note'] : "";

            foreach ($order_code as $key => $val) {
                $res->{$key} = $val;
            }

            if ($old_shipping_status != $order_code['shipping_status']) {
                $transaction['order_id'] = $id;
                $transaction['shipping_status'] = $order_code['shipping_status'];
                $transaction['description'] = 'Update shipping from ' . $old_shipping_status . ' to ' . $order_code['shipping_status'];
                Transaction::forceCreate($transaction);
            }

            if ($old_payment_status != $order_code['payment_status']) {
                $transaction['order_id'] = $id;
                $transaction['shipping_status'] = $order_code['payment_status'];
                $transaction['description'] = 'Update payment from ' . $old_payment_status . ' to ' . $order_code['payment_status'];
                Transaction::forceCreate($transaction);
            }

            if ($notes) {
                $transaction['order_id'] = $id;
                $transaction['description'] = $notes;
                Transaction::forceCreate($transaction);
            }

            if ($old_status != $order_code['order_status']) {
                $transaction['order_id'] = $id;
                $transaction['description'] = 'Order change status to ' . $order_code['order_status'];
                Transaction::forceCreate($transaction);
            }


            return $res->update();
        }

        if ($order_code && $nl_token && $success) {
            $order = Order::where('title', '=', $order_code)->first();
            if ($order->nl_token == $nl_token) {
                Cart::where('user_id', $user_id)->delete();
                return Order::where('title', '=', $order_code)->update(['payment_status' => 'completed', 'type' => 'ordered']);
            }
        }

        if ($order_code && $nl_token) {
            return Order::where('title', '=', $order_code)->update(['nl_token' => $nl_token]);
        }

        /*if ($order_code) {
            Cart::where('user_id', $user_id)->delete();
            return Order::where('title', '=', $order_code)->update(['payment_status' => 'completed']);
        }*/


        return false;
    }

    public function searchOrders($request){
        $payment_status = isset($request['payment_status']) ? $request['payment_status'] : '';
        $email = isset($request['email']) ? $request['email'] : '';
        $fullname = isset($request['fullname']) ? $request['fullname'] : '';
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $order_code = isset($request['order_code']) ? $request['order_code'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';

        $limit = $request['limit'];
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $list = Order::where('deleted', '!=', 1)->where('type', '!=', 'draft');
        if ($order_code) {
            return Order::where('order_code', '=', $order_code)->first();
        }

        if ($keyword) {
            $list->where('title', 'regexp', '/.*'.$keyword.'/i');
        }

        if($payment_status){
            $list->where('payment_status', '=', $payment_status);
        }

        if($email){
            $list->where('customer.email', '=', $email);
        }

        if($fullname){
            $list->where('customer.fullname', '=', $fullname);
        }

        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }
        $response = $list->orderBy('created_at', 'desc')->paginate(intval($limit));

        $res = $response->toArray();

        if (count($res['data'])) {
            foreach ($res['data'] as $key => $item) {
                $id = $item['_id'];
                $res['data'][$key]['id'] = $id;
                $res['data'][$key]['note'] = '';
                $res['data'][$key]['transactions'] = $this->getTransactions($id);
            }
        }

        return $res;
    }

    public function user()
    {
        return $this->hasOne('App\Models\User');
    }
}