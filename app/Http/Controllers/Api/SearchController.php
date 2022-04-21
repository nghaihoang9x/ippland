<?php

namespace App\Http\Controllers\Api;

use App\Models\Search;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Collection;
use App\Models\Vendor;
use App\Models\Product;

class SearchController extends ApiController
{

    public function search(Request $request) {
        $keyword = $request->get('k');
        $ajax = $request->get('ajax');
        $save = $request->get('save');

        $ids = $result = [];
        $limit = 5;

        if ($save) {
            $data['title'] = $keyword;
            $model = New Search();
            $model->saveSearch($data);
        }

        $exist = Vendor::where('deleted', '!=', 1)->where('seo_alias', '=', $keyword)->first();
        if ($exist && $ajax) {
            $result['vendor'] = $exist;
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
        } else {
            $CollectionModel = new Collection();
            $result['resultCollection'] = $CollectionModel->listCollection($ids, $limit, $keyword);

            $VendorModel = new Vendor();
            $result['resultVendor'] = $VendorModel->listVendor($ids, $limit, $keyword);

            $ProductModel = new Product();
            $result['resultProduct'] = $ProductModel->listProduct($ids, $limit, $keyword);

            $ProductModel = new Search();
            $result['resultSuggest'] = $ProductModel->listSearch($ids, 30, $keyword);

            $result['vendor'] = '';
        }


        if ($result) {
            $result['success'] = true;
            $result['response_time'] = microtime(true) - LARAVEL_START;
            return $this->responseSuccess($result);
        }
        return $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function saveKeyword() {

    }

    public function generateSlug($str, $key = ''){
        $string = str_replace(' ', '-', strtolower($str)); // Replaces all spaces with hyphens.
        $string = str_replace(['á','à','ã','ạ','ả','â','ấ','ầ','ẫ','ậ','ẩ','ắ','ẵ','ằ','ặ','ẳ', 'ă'], 'a', $string);
        $string = str_replace(['Á','À','Ã','Ạ','Ả','Â','Ấ','Ầ','Ẫ','Ậ','Ẩ','Ắ','Ẵ','Ằ','Ặ','Ẳ', 'Ă'], 'a', $string);
        $string = str_replace(['đ', "Đ"], 'd', $string);
        $string = str_replace(['é','è','ẽ','ẹ','ẻ','ê','ế','ề','ễ','ệ','ể'], 'e', $string);
        $string = str_replace(['É','È','Ẽ','Ẹ','Ẻ','Ê','Ế','Ề','Ễ','Ệ','Ể'], 'e', $string);
        $string = str_replace(['í','ì','ĩ','ị','ỉ','Í','Ì','Ĩ','Ị','Ỉ'], 'i', $string);
        $string = str_replace(['ó','ò','õ','ọ','ỏ','ô','ố','ồ','ỗ','ộ','ổ','ơ','ớ','ờ','ỡ','ợ','ở'], 'o', $string);
        $string = str_replace(['Ó','Ò','Õ','Ọ','Ỏ','Ô','Ố','Ồ','Ỗ','Ộ','Ổ','Ơ','Ớ','Ờ','Ỡ','Ợ','Ở'], 'o', $string);
        $string = str_replace(['ú','ù','ũ','ụ','ủ','ư','ứ','ừ','ữ','ự','ử'], 'u', $string);
        $string = str_replace(['Ú','Ù','Ũ','Ụ','Ủ','Ư','Ứ','Ừ','Ữ','Ự','Ử'], 'u', $string);
        $string = str_replace(['ý','ỳ','ỹ','ỵ','ỷ'], 'y', $string);
        $string = str_replace(['Ý','Ỳ','Ỹ','Ỵ','Ỷ'], 'y', $string);
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

        $i = 1;$slug = $string;
        while ($key != '') {
            $model = self::where($key, $string)->first();
            if(!$model || $model->_id == $this->_id)
                return $string;
            $string = $slug.$i;
            ++$i;
        }
        return $string;
    }
}
