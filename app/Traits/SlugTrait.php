<?php
namespace App\Traits;
trait SlugTrait{
    public function generateSlug($str, $key = '', $index = false){
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

        if ($index) return $string;

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