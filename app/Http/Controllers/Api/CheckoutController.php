<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class CheckoutController extends ApiController
{
    public function saveOrder()
    {
        $post = request()->all();
        $order_number = $this->generateOrder();
        if (Order::where('name', $order_number)->first()) {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error save order");
        }
        $post['title'] = $order_number;

        if ($post['is_admin']) {
            $res = Order::forceCreate($post);


            $res['response_time'] = microtime(true) - LARAVEL_START;
            if ($res) {
                $transaction['order_id'] = $res->_id;
                $transaction['shipping_status'] = $res->shipping['status'];
                $transaction['payment_status'] = $res->payment['status'];
                $transaction['description'] = 'Đơn hàng được tạo';

                Transaction::forceCreate($transaction);
            }
            return $this->responseSuccess($res);
        } else {
            $user = User::checkToken();
            if ($user == 'expired') return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Vui lòng đăng nhập lại");
            if ($user && $user != 'expired'){
                $post['user_id'] = $user->_id;
                $res = Order::forceCreate($post);
                $res['response_time'] = microtime(true) - LARAVEL_START;

                if ($res) {
                    $transaction['order_id'] = $res->_id;
                    $transaction['shipping_status'] = $res->shipping['status'];
                    $transaction['payment_status'] = $res->payment['status'];
                    $transaction['description'] = 'Đơn hàng được tạo';
                    Transaction::forceCreate($transaction);
                }

                return $this->responseSuccess($res);
            }
        }

        return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Error save order");
    }

    public function generateOrder() {
        //$today = date("Ym");
        $rand = substr(hexdec(md5(microtime())),2,9);
        return $unique = $rand;
    }
    public function getOrder(Request $request) {
        $OrderModel = new Order();
        $ids = $request->get('ids');
        $order_code = $request->get('order_code');

        $limit = $request->get('limit');
        if ($ids) {
            $ids = explode(',', $ids);
        }


        $result = $OrderModel->listOrders($ids, $limit, $order_code);

        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function updateOrder(Request $request) {
        $user = User::checkToken();

        if (!$user || $user == 'expired'){
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Vui lòng đăng nhập lại");
        }

        $OrderModel = new Order();
        $order_code = $request->get('order_code');
        $result = $OrderModel->updateOrder($order_code);
        if ($result) {
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function delete(Request $request) {
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
            $res['response_time'] = microtime(true) - LARAVEL_START;
            $res['success'] = true;
            $res['request'] = $request->all();
            $res['request']['note'] = '';
            $res['request']['transactions'] = $OrderModel->getTransactions($id);
            return $this->responseSuccess($res);
        } else {
            return $this->sendError("Bad Request", \Illuminate\Http\Response::HTTP_BAD_REQUEST, "Order Not Found");
        }
    }
}
