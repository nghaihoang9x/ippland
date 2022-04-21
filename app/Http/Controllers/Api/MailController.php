<?php

namespace App\Http\Controllers\Api;

use App\Events\PaymentStatusOrderChanged;
use App\Events\ShippingStatusOrderChanged;
use App\Events\StatusOrderChanged;
use App\Models\Order;
use App\Models\Transaction;
use App\Http\Controllers\Api\ApiController;

class MailController extends ApiController
{
    public function send()
    {
        $get = request()->all();
        $res = [];
        $order_id = isset($get['order_id']) ? trim($get['order_id']) : '';
        $type = isset($get['type']) ? trim($get['type']) : '';

        $order = Order::find($order_id);
        $res['status'] = 'fail';
        if(isset($order->_id) && ($type == 'confirm' || $type == 'confirmed')) {

            $transaction['order_id'] = $order_id;
            $transaction['type'] = $type == 'confirm' || $type == 'confirmed' ? 'resend_confirm_email' : 'send_shipping_email';
            $transaction['description'] = $type == 'confirm' || $type == 'confirmed' ? 'Đã gửi email xác nhận đơn hàng đến khách hàng' : 'Đã gửi email thông tin vận chuyển đến khách hàng';
            Transaction::forceCreate($transaction);
            if($type == 'confirm' || $type == 'confirmed')
                event(new StatusOrderChanged($order, 'confirmed'));
            else
                event(new ShippingStatusOrderChanged($order, 'shipping'));
            $res['status'] = 'success';
        }else if(isset($order->_id) && $type == 'payment_fail'){
            $transaction['order_id'] = $order_id;
            $transaction['type'] = 'payment_order_email';
            $transaction['description'] = 'Đặt hàng và thanh toán thất bại';
            Transaction::forceCreate($transaction);

            $order->email_status = 'payment_fail';
            event(new PaymentStatusOrderChanged($order));
            $res['status'] = 'success';
        }
        return $this->responseSuccess($res);
    }
}
