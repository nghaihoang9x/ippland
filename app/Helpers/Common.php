<?php

namespace App\Helpers;

use App\Models\NavigationItem;
use App\Models\Product;
use App\Models\Project;
use App\Models\Subscription;

class Common
{
    public static function generateSlug($str)
    {
        $string = str_replace(' ', '-', $str); // Replaces all spaces with hyphens.
        $string = str_replace(['á', 'à', 'ã', 'ạ', 'ả', 'â', 'ấ', 'ầ', 'ẫ', 'ậ', 'ẩ', 'ắ', 'ẵ', 'ằ', 'ặ', 'ẳ', 'ă'], 'a', $string);
        $string = str_replace(['é', 'è', 'ẽ', 'ẹ', 'ẻ', 'ê', 'ế', 'ề', 'ễ', 'ệ', 'ể'], 'e', $string);
        $string = str_replace(['í', 'ì', 'ĩ', 'ị', 'ỉ'], 'i', $string);
        $string = str_replace(['ó', 'ò', 'õ', 'ọ', 'ỏ', 'ô', 'ố', 'ồ', 'ỗ', 'ộ', 'ổ', 'ơ', 'ớ', 'ờ', 'ỡ', 'ợ', 'ở'], 'o', $string);
        $string = str_replace(['ú', 'ù', 'ũ', 'ụ', 'ủ', 'ư', 'ứ', 'ừ', 'ữ', 'ự', 'ử'], 'u', $string);
        $string = str_replace(['ý', 'ỳ', 'ỹ', 'ỵ', 'ỷ'], 'y', $string);

        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

        $string = strtolower($string);

        return $string;
    }

    public static function updateSeoAlias($seo_alias, $id, $collection)
    {

        NavigationItem::where('root_id', $id)->where('type', $collection)->update(['item_alias' => $seo_alias], ['upsert' => true]);
    }

    //return order status
    public static function order_status($status)
    {
        $status_map = array(
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'completed' => 'Đã hoàn thành',
            'canceled' => 'Đã hủy',
            'returned' => 'Đã hoàn trả'
        );
        $status_text = isset($status_map[$status]) ? $status_map[$status] : '-';
        return $status_text;
    }

    //return payment status
    public static function payment_status($status)
    {
        $status_map = array(
            'incomplete' => 'Chưa thanh toán',
            'completed' => 'Đã thanh toán',
            'refunded' => 'Đã hoàn tiền'
        );
        $status_text = isset($status_map[$status]) ? $status_map[$status] : '-';
        return $status_text;
    }

    //return shipping status
    public static function shipping_status($status)
    {
        $status_map = array(
            'incomplete' => 'Chưa giao',
            'available' => 'Sẵn sàng giao hàng',
            'shipping' => 'Đang giao hàng',
            'completed' => 'Đã giao hàng',
            'returned' => 'Đã hoàn trả'
        );
        $status_text = isset($status_map[$status]) ? $status_map[$status] : '-';
        return $status_text;
    }

    public static function full_shipping_address($shipping_address)
    {
        $full_address = null;
        if (isset($shipping_address->address) && !empty($shipping_address->address))
            $full_address .= $shipping_address->address;
        if (isset($shipping_address->ward_display) && !empty($shipping_address->ward_display))
            $full_address .= ', ' . $shipping_address->ward_display;
        if (isset($shipping_address->district_display) && !empty($shipping_address->district_display))
            $full_address .= ', ' . $shipping_address->district_display;
        if (isset($shipping_address->city_display) && !empty($shipping_address->city_display))
            $full_address .= ', ' . $shipping_address->city_display;

        return $full_address;
    }

    public static function money_format($number)
    {
        return $number ? number_format(intval($number), 0, null, '.') . 'đ' : '0đ';
    }

    public static function get_payment_method($method)
    {
        switch ($method) {
            case 'BANK_TRANSFER':
                $payment_method = 'Chuyển khoản ngân hàng';
                break;
            case 'CASH':
                $payment_method = 'Thanh toán tiền mặt';
                break;
            case 'ATM':
                $payment_method = 'Thẻ thanh toán nội địa ATM';
                break;
            case 'VISA':
                $payment_method = 'Thẻ thanh toán quốc tế';
                break;
            case 'COD':
                $payment_method = 'Thanh toán khi nhận hàng (COD)';
                break;
            case 'CREDIT_CARD':
                $payment_method = 'Thẻ tín dụng (Credit card)';
                break;
            case 'MOMO':
                $payment_method = 'Ví điện tử MoMo';
                break;
            default:
                $payment_method = 'Thanh toán online';
                break;
        }

        return $payment_method;
    }

    public static function random_string($length = 5)
    {
        $str = "";
        $characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }
        return $str;
    }

    public static function lang($data, $key, $default = '', $lang = 'vi')
    {

        if (isset($data->{"{$key}_{$lang}"}) && !empty(trim($data->{"{$key}_{$lang}"}))) {
            return $data->{"{$key}_{$lang}"};
        } else {
            if (isset($data->{$key})) {
                return $data->{$key};
            }
        }
        return $default;

    }

    public static function standardize_model_value_type($val, $key = '')
    {
        $exept = ['address_city', 'address_state', 'address_ward'];
        if (in_array($key, $exept)) return $val;
        if (is_numeric($val)) $val = intval($val);
        if ($val == 'true' || $val == 'false') $val = boolval($val);

        return $val;
    }

    public static function mapProjectSeoAliasArray($products)
    {
        if (is_array($products)) {
            foreach ($products['data'] as $key => $product) {
                if (isset($product['project_parent_name'])) {
                    $project_id = $product['project_parent_name'];
                    $project = Project::getById($project_id);
                    if ($project) {
                        $product['project_seo_alias'] = $project->seo_alias ?? '';
                    } else {
                        $product['project_seo_alias'] = '';
                    }
                    $products['data'][$key] = $product;
                }
            }
        }

        return $products;
    }

    public static function mapProjectSeoAliasDetail($product)
    {
        $project_id = $product->project_parent_name;
        $project = Project::getById($project_id);
        if ($project) {
            $product->project_seo_alias = $project->seo_alias ?? '';
        }

        return $product;
    }
}