<?php
namespace App\Http\Controllers\Api;

use App\Models\Discount;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ScriptController extends ApiController
{
    public function updateDiscountCount(Request $request){

        $data = [];

        $discounts = Discount::where('deleted', '!=', '1')->get(['used', 'title']);
        if($discounts){
            foreach ($discounts as $discount){
                $orders = Order::where('type', '!=', 'draft')->where('discounts_code', 'like', "{$discount->title}")->count();
                $discount->used = intval($orders);
                $discount->update();
            }
        }

        return $this->responseSuccess($data);

    }

    public function updateShipping(Request $request){
        $shipping_transactions = Transaction::where('description', 'Update shipping from incomplete to available')->get();
        if($shipping_transactions){
            foreach ($shipping_transactions as $transaction){
                $order = Order::where('_id', $transaction->order_id)->first();
                if($order && (!isset($order->shipping_updated_at) || empty($order->shipping_updated_at) || is_object($order->shipping_updated_at) || is_array($order->shipping_updated_at))){
                    $order->shipping_updated_at = date('Y-m-d H:i:s', strtotime($transaction->created_at));
                    $order->update();
                }
            }
        }

        $shipped_transactions = Transaction::where('description', 'Update shipping from shipping to completed')->get();
        if($shipped_transactions){
            foreach ($shipped_transactions as $transaction){
                $order = Order::where('_id', $transaction->order_id)->first();
                if($order && (!isset($order->shipped_at) || empty($order->shipped_at) || is_object($order->shipped_at) || is_array($order->shipped_at))){
                    $order->shipped_at = date('Y-m-d H:i:s', strtotime($transaction->created_at));
                    $order->update();
                }
            }
        }
    }


}