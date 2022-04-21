<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;
use Illuminate\Support\Facades\Hash;
use DateTime;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;

class Customer extends Eloquent
{

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'customers';

    protected $fillable = ['email', 'phone', 'from', 'fullname', 'shipping_address', 'children', 'password', 'password_confirm', 'deleted'];

    public static $ATTR = [];

    public function validateSave()
    {
        return true;
    }

    public function saveCustomer($data)
    {
        $res = new Customer();
        foreach ($data as $key => $val) {
            $res->{$key} = $val;
        }

        return $res->save();
    }

    public function updateAddress($user_id, $addresses)
    {
        UserAddresses::where('user_id', $user_id)->delete();
        foreach ($addresses as $_address) {
            if (isset($_address['_id'])) {
                unset($_address['_id']);
            }
            $_address['user_id'] = $user_id;
            UserAddresses::forceCreate($_address);
        }
    }

    public function updateCustomer($data, $id)
    {
        $res = Customer::find($id);
        $old_pwd = $res->password;
        $shipping_address = [];
        unset($data['_id']);
        foreach ($data as $key => $val) {

            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false) {
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            } else $res->{$key} = $val;

            if ($key == 'shipping_address') {
                $shipping_address = $val;
            }

            if ($key == 'password') {
                $text = substr(md5('deodoipassdau'), 2, 10);
                if ($text != $val) {
                    $res->{$key} = Hash::make($val);
                } else {
                    $res->{$key} = $old_pwd;
                }
            }
        }
        if ($id && count($shipping_address) > 0) {
            //$this->updateAddress($res->_id, $shipping_address);
        };
        unset($res->total_order);

        return $res->update();
    }

    public function listCustomer($request)
    {
        $get_own_only = isset($request['own']) ? $request['own'] : false;
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $full_name = isset($request['fullname']) ? $request['fullname'] : '';
        $phone = isset($request['phone']) ? $request['phone'] : '';
        $code = isset($request['code']) ? $request['code'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        $start = isset($request['start_date']) ? $request['start_date'] : '';
        $stop = isset($request['end_date']) ? $request['end_date'] : '';
        $p_type = isset($request['ptype']) ? $request['ptype'] : '';

        if ($ids) {
            $ids = explode(',', $ids);
        }
        $list = Customer::where('deleted', '!=', 1);

        if ($get_own_only) {
            JWTAuth::parseToken();
            $token = JWTAuth::getToken();
            if (!$token) return [];
            $user = JWTAuth::toUser($token);
            $list->where('user_id', '=', $user->_id);
        }
        if ($keyword) {
            $list->where(function ($query) use ($keyword) {
                $query->where('fullname', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('code', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('phone', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('email', 'LIKE', '%' . $keyword . '%');
            });
        }
        if ($full_name) {
            $list->where('fullname', 'LIKE', '%' . $full_name . '%');
        }
        if ($phone) {
            $list->where('phone', 'LIKE', '%' . $phone . '%');
        }
        if ($code) {
            $list->where('code', 'LIKE', '%' . $code . '%');
        }
        if ($p_type) {
            if ($p_type === 'use') {
                $list->where(function ($query) {
                    $query->where('fneed', 'torent')
                        ->orWhere('fneed', 'buy');
                });
            }
            if ($p_type === 'owner') {
                $list->where(function ($query) {
                    $query->where('fneed', 'rent')
                        ->orWhere('fneed', 'sell');
                });
            }
        }
        if ($request) {
            unset($request['options']);
            unset($request['device_token']);
            unset($request['carts']);
            foreach ($request as $k => $v) {
                if (!in_array($k, ['ids', 'limit', 'keyword', 'start_date', 'end_date', 'page', 'options', 'device_token',
                    'carts', 'api_token', 'own', 'ptype', 'level', 'fullname'])) {
                    if ($v == 'false' || $v == 'true')
                        $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                    $list->where($k, $v);
                }
            }
        }

        if ($start && $stop) {
            $sdt = Carbon::createFromFormat('Y-m-d', $start);
            $edt = Carbon::createFromFormat('Y-m-d', $stop);
            $list = $list->where('created_at', '>', Carbon::create($sdt->year, $sdt->month, $sdt->day, 0, 0, 0));
            $list = $list->where('created_at', '<', Carbon::create($edt->year, $edt->month, $edt->day, 23, 58, 0));
            $customers = $list->get()->toArray();

            $res = [];
            foreach ($customers as $_customer) {
                $user_id = $_customer['_id'];

                $total = Order::where('user_id', $user_id)->where('type', '!=', 'draft')->get();
                $_customer['total_order'] = $total;
                $_customer['shipping_address']['children'] = isset($_customer['children']) ? $_customer['children'] : '';
                $_customer['shipping_address']['city'] = isset($_customer['shipping_address']['city']) ? $_customer['shipping_address']['city'] : '';
                $_customer['shipping_address']['city_display'] = isset($_customer['shipping_address']['city_display']) ? $_customer['shipping_address']['city_display'] : '';
                $_customer['shipping_address']['district'] = isset($_customer['shipping_address']['district']) ? $_customer['shipping_address']['district'] : '';
                $_customer['shipping_address']['district_display'] = isset($_customer['shipping_address']['district_display']) ? $_customer['shipping_address']['district_display'] : '';
                $_customer['shipping_address']['ward'] = isset($_customer['shipping_address']['ward']) ? $_customer['shipping_address']['ward'] : '';
                $_customer['shipping_address']['ward_display'] = isset($_customer['shipping_address']['ward_display']) ? $_customer['shipping_address']['ward_display'] : '';

                $_customer['password'] = substr(md5('deodoipassdau'), 2, 10);
                $_customer['children'] = isset($_customer['children']) ? $_customer['children'] : [];
                $_customer['fullname'] = isset($_customer['fullname']) ? $_customer['fullname'] : '';
                $_customer['phone'] = isset($_customer['phone']) ? $_customer['phone'] : '';
                $_customer['tags'] = isset($_customer['tags']) ? $_customer['tags'] : [];
                $res[] = $_customer;

            }

            $customers = $res;

            return $customers;
        }

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }
        $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));

        $customers = $response->toArray();
        $res = [];
        foreach ($customers['data'] as $_customer) {
            $user_id = $_customer['_id'];

            if (is_array($ids) && count($ids)) {
                $total = Order::where('user_id', $user_id)->where('type', '!=', 'draft')->get();
                $_customer['tags'] = isset($_customer['tags']) ? $_customer['tags'] : [];
            } else {
                $total = Order::where('user_id', $user_id)->where('type', '!=', 'draft')->count();
                $_customer['tags'] = isset($_customer['tags']) ? Tag::getTags($_customer['tags']) : [];
            }
            $_customer['total_order'] = $total;
            $_customer['shipping_address']['children'] = isset($_customer['children']) ? $_customer['children'] : '';
            $_customer['shipping_address']['city'] = isset($_customer['shipping_address']['city']) ? $_customer['shipping_address']['city'] : '';
            $_customer['shipping_address']['city_display'] = isset($_customer['shipping_address']['city_display']) ? $_customer['shipping_address']['city_display'] : '';
            $_customer['shipping_address']['district'] = isset($_customer['shipping_address']['district']) ? $_customer['shipping_address']['district'] : '';
            $_customer['shipping_address']['district_display'] = isset($_customer['shipping_address']['district_display']) ? $_customer['shipping_address']['district_display'] : '';
            $_customer['shipping_address']['ward'] = isset($_customer['shipping_address']['ward']) ? $_customer['shipping_address']['ward'] : '';
            $_customer['shipping_address']['ward_display'] = isset($_customer['shipping_address']['ward_display']) ? $_customer['shipping_address']['ward_display'] : '';

            $_customer['password'] = substr(md5('deodoipassdau'), 2, 10);
            $_customer['children'] = isset($_customer['children']) ? $_customer['children'] : [];
            $_customer['fullname'] = isset($_customer['fullname']) ? $_customer['fullname'] : '';
            $_customer['phone'] = isset($_customer['phone']) ? $_customer['phone'] : '';

            $res[] = $_customer;

        }

        $customers['data'] = $res;

        return $customers;
    }

    public function listCustomerByIds($ids)
    {
        if (count($ids)) {
            $res = [];
            if (is_array($ids) && count($ids)) {
                $categories = Customer::whereIn('id', $ids)->get();
                $i = 0;
                foreach ($categories as $_Customer) {
                    $res[$i]['label'] = $_Customer->title;
                    $res[$i]['value'] = $_Customer->_id;
                    $i++;
                }
            }
            return $res;
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function deleteOnly($id)
    {
        $lead = self::find($id)->update([
            'deleted' => 1
        ]);

        return $lead;
    }
}