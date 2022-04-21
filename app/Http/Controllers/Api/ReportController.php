<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends ApiController
{

    public function index(Request $request){
        $reportModel = new Report();
        $get = $request->all();
        $result['data'] = [
            'order' => [
                'all' => $reportModel->getTotalOrders($get),
                'completed' => $reportModel->getTotalOrdersByStatus($get, 'completed'),
                'canceled' => $reportModel->getTotalOrdersByStatus($get, 'canceled'),
                'pending' => $reportModel->getTotalOrdersByStatus($get, 'pending'),
                'confirmed' => $reportModel->getTotalOrdersByStatus($get, 'confirmed'),
            ],
            'shipping' => [
                'incomplete' => $reportModel->getTotalOrdersByShipping($get, 'incomplete'),
                'available' => $reportModel->getTotalOrdersByShipping($get, 'available'),
                'completed' => $reportModel->getTotalOrdersByShipping($get, 'completed'),
                'returned' => $reportModel->getTotalOrdersByShipping($get, 'returned'),
                'shipping' => $reportModel->getTotalOrdersByShipping($get, 'shipping'),
            ],
            'payment' => [
                'incomplete' => $reportModel->getTotalOrdersByPayment($get, 'incomplete'),
                'completed' => $reportModel->getTotalOrdersByPayment($get, 'completed'),
                'refunded' => $reportModel->getTotalOrdersByPayment($get, 'refunded'),
            ],
            'customers' => $reportModel->listCustomers($get),
            'revenue' => [
                'all' => $reportModel->getRevenueAllOrders($get),
                'paid' => $reportModel->getRevenuePaidOrders($get),
                'pending' => $reportModel->getRevenuePendingOrders($get),
                'canceled' => $reportModel->getRevenueCanceledOrders($get),
            ],
            'best_seller' => $reportModel->getTopSeller($get)
        ];

        if ($result) {
            //$result['response_time'] = microtime(true) - LARAVEL_START;
            $result['success'] = true;
            return $this->responseSuccess($result);
        }

        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function orders(Request $request){
        $reportModel = new Report();
        $get = $request->all();
        $res = [
            'data' => [
            'report' => [
                    'completed' => $reportModel->orderReport($get, 'completed'),
                    'canceled' => $reportModel->orderReport($get, 'canceled'),
                    'pending' => $reportModel->orderReport($get, 'pending'),
                    'confirmed' => $reportModel->orderReport($get, 'confirmed')
                ]
                ]
        ];
        $res['success'] = true;
        return $this->responseSuccess($res);
    }
}
