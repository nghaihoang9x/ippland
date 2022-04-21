<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Carbon\Carbon;
use MongoDB\BSON\UTCDateTime;

class Report extends Eloquent {

    protected $connection = 'mongodb';

    public function queryOrders($request){
        $start = isset($request['start_date']) ? $request['start_date'] : '';
        $stop = isset($request['end_date']) ? $request['end_date'] : '';
        $type = isset($request['type']) ? $request['type'] : 'created_at';
        $list = Order::where('deleted', '!=', 1)->where('type', '!=', 'draft');
        if ($start && $stop) {

            $sdt = Carbon::createFromFormat('Y-m-d', $start);
            $edt = Carbon::createFromFormat('Y-m-d', $stop);
            $list = $list->where(function ($query) use ($type, $sdt, $start){
                $query->where($type, '>', Carbon::create($sdt->year, $sdt->month, $sdt->day, 0, 0, 0))
                    ->orWhere($type, '>', $start.' 00:00:00');
            });

            $list = $list->where(function ($query) use ($type, $edt, $stop){
                $query->where($type, '<', Carbon::create($edt->year, $edt->month, $edt->day, 23, 59, 59))
                    ->orWhere($type, '<', $stop.' 23:59:59');
            });

        }
        $list = $list->orderBy($type, 'desc');

        return $list;
    }

    //get list orders by start and end date
    public function listOrders($request){
        $start = isset($request['start_date']) ? $request['start_date'] : '';
        $stop = isset($request['end_date']) ? $request['end_date'] : '';
        $type = isset($request['type']) ? $request['type'] : 'created_at';
        $list = $this->queryOrders($request);
        if ($start && $stop) {

            $list = $list->orderBy($type, 'desc');
            return $list->get(['cnote', 'is_alternative', 'payment_status', 'shipping_method', 'shipping_status', 'shipping_shipper', 'shipping_tracking_code', 'buyer_type', 'title', 'status', 'subtotal_price', 'total_price', 'total_discounts', 'is_create_account', 'user_id', 'updated_at', 'created_at', 'note', 'type']);
        }

        return $list;
    }

    //get list customers by start and end date
    public function listCustomers($request){
        $start = isset($request['start_date']) ? $request['start_date'] : '';
        $stop = isset($request['end_date']) ? $request['end_date'] : '';
        $type = isset($request['type']) ? $request['type'] : 'created_at';
        $list = Customer::where('deleted', '!=', 1);
        if ($start && $stop) {

            $sdt = Carbon::createFromFormat('Y-m-d', $start);
            $edt = Carbon::createFromFormat('Y-m-d', $stop);
            $list = $list->where(function ($query) use ($type, $sdt, $start){
                $query->where($type, '>', Carbon::create($sdt->year, $sdt->month, $sdt->day, 0, 0, 0))
                    ->orWhere($type, '>', $start.' 00:00:00');
            });

            $list = $list->where(function ($query) use ($type, $edt, $stop){
                $query->where($type, '<', Carbon::create($edt->year, $edt->month, $edt->day, 23, 59, 59))
                    ->orWhere($type, '<', $stop.' 23:59:59');
            });

            return $list->count();
        }

        return $list->count();
    }

    public function getTopSeller($request){
        $start = isset($request['start_date']) ? $request['start_date'] : '';
        $stop = isset($request['end_date']) ? $request['end_date'] : '';
        if ($start && $stop) {

            $dateBegin              = new UTCDateTime(strtotime($start)*1000);
            $dateEnd                = new UTCDateTime(strtotime($stop)*1000);

            $variants = OrderItem::raw(function($collection) use ($dateBegin, $dateEnd) {
                return $collection->aggregate([
                    [
                        '$match' => [
                            'created_at' => ['$gte' => $dateBegin, '$lte' => $dateEnd]
                        ]
                    ],
                    [
                        '$group' => [
                            '_id' => '$variant_id',
                            'total_sales' => ['$sum' => '$quantity'],
                        ]
                    ],
                    [
                        '$sort' => ['total_sales' => -1]
                    ],
                    [
                        '$limit' => 10
                    ],
                ]);
            });
        }else{
            $variants = OrderItem::raw(function($collection) {
                return $collection->aggregate([
                    [
                        '$group' => [
                            '_id' => '$variant_id',
                            'total_sales' => ['$sum' => '$quantity'],
                        ],
                    ],
                    [
                        '$sort' => ['total_sales' => -1],
                    ],
                    [
                        '$limit' => 10
                    ]
                ]);
            });
        }

        $res = [];
        if($variants){
            foreach ($variants as $key => $variant){
                $variant_ = Variant::where(function ($query) use ($variant){
                    $query->orWhere('_id', $variant->_id);
                    $query->orWhere('id', $variant->_id);
                    $query->orWhere('variant_id', $variant->_id);
                })->first();
                $product_ = isset($variant_->product_id) ? Product::find($variant_->product_id) : [];

                $res[$key] = $product_;
                $res[$key]['variants'] = [$variant_];
                $res[$key]['total_sales'] = $variant->total_sales;
            }
        }
        return $res;
    }

    public function getTotalOrders($request){
        $list = $this->queryOrders($request);
        $list = $list->count();
        return $list;
    }

    public function getTotalOrdersByStatus($request, $status){
        $start = isset($request['start_date']) ? $request['start_date'] : '';
        $stop = isset($request['end_date']) ? $request['end_date'] : '';
        if(!$start && !$stop) {
            switch ($status) {
                case 'canceled':
                    $type = 'canceled_at';
                    break;
                case 'pending':
                    $type = 'created_at';
                    break;
                default:
                    $type = 'updated_at';
                    break;
            }
            $request['type'] = $type;
        }
        $list = $this->queryOrders($request);
        $list = $list->where('order_status', $status);
        $list = $list->count();
        return $list;
    }

    public function getTotalOrdersByPayment($request, $status){
        switch ($status){
            case 'refunded':
                $type = 'refunded_at';
                break;
            default:
                $type = 'updated_at';
                break;
        }
        $request['type'] = $type;
        $list = $this->queryOrders($request);
        $list = $list->where('payment_status', $status);
        $list = $list->count();
        return $list;
    }

    public function getTotalOrdersByShipping($request, $status){
        switch ($status){
            case 'returned':
                $type = 'returned_at';
                break;
            case 'available':
                $type = 'available_at';
                break;
            case 'completed':
                $type = 'shipped_at';
                break;
            case 'shipping':
                $type = 'shipping_updated_at';
                break;
            default:
                $type = 'updated_at';
                break;
        }
        $request['type'] = $type;
        $list = $this->queryOrders($request);
        $list = $list->where('shipping_status', $status);

        $list = $list->count();
        return $list;
    }

    public function getRevenueAllOrders($request){
        $list = $this->queryOrders($request);
        $start = isset($request['start_date']) ? $request['start_date'] : '';
        $stop = isset($request['end_date']) ? $request['end_date'] : '';
        if(!$start && !$stop) {
            $list = $list->where('order_status', '!=', 'canceled');
        }
        $list = $list->sum('total_price');
        return $list;
    }

    public function getRevenuePaidOrders($request){
        $list = $this->queryOrders($request);
        $list = $list->where('payment_status', '=', 'completed');
        $list = $list->where('order_status', '!=', 'canceled');
        $list = $list->sum('total_price');
        return $list;
    }

    public function getRevenuePendingOrders($request){
        $list = $this->queryOrders($request);
        $list = $list->where('order_status', '!=', 'canceled');
        $list = $list->where('payment_status', '=', 'incomplete');
        $list = $list->sum('total_price');
        return $list;
    }

    public function getRevenueCanceledOrders($request){
        $list = $this->queryOrders($request);
        $list = $list->where('status', '=', 'canceled');
        $list = $list->sum('total_price');
        return $list;
    }

    public function orderReport($request, $status = ''){
        $res = $data = [];
        $start_date = isset($request['start_date']) ? $request['start_date'] : '';
        $end_date = isset($request['end_date']) ? $request['end_date'] : '';
        $begin = new \DateTime( $start_date );
        $end   = new \DateTime( $end_date );
        $type = isset($request['type']) ? $request['type'] : 'created_at';
        $list = $this->queryOrders($request);
        $list->select(['total_price', $type]);
        if($status)
            $list->where('order_status', $status);
        $orders = $list->get();
        if($orders){
            foreach ($orders as $order){
                $date = date('Y-m-d', strtotime($order->{$type}));
//                if($start_date === $end_date)
//                    $date = date('H', strtotime($order->{$type}));
                $data[$date] = (isset($data[$date]) ? $data[$date] : 0) + $order->total_price;
            }
        }

        if($start_date === $end_date){
//            for($hour = 1; $hour < 13; $hour++){
//                $res['data'][$hour] = isset($data[$hour]) ? $data[$hour] : 0;
//            }
            $res[$start_date] = isset($data[$start_date]) ? $data[$start_date] : 0;
        }else {
            $interval = \DateInterval::createFromDateString('1 day');
            $period = new \DatePeriod($begin, $interval, $end);
            foreach ($period as $dt) {
                $date = $dt->format("Y-m-d");
                $res[$date] = isset($data[$date]) ? $data[$date] : 0;
            }
        }

        return $res;
    }

}