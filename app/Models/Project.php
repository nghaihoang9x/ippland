<?php

namespace App\Models;

use App\Helpers\Common;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\SlugTrait;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;

class Project extends Eloquent
{

    use SlugTrait;
    protected $connection = 'mongodb';
    protected $collection = 'projects';
    protected $guarded = [];
    public static $ATTR = [];

    public function validateSave()
    {
        return true;
    }

    public static function boot()
    {
        parent::boot();
        self::retrieved(function ($model) {
            $city = isset($model->address_city) ? $model->address_city . '' : '';
            $state = isset($model->address_state) ? $model->address_state . '' : '';
            $ward = isset($model->address_ward) ? $model->address_ward . '' : '';
            $geos = Geo::whereIn('code', [$city, $state, $ward])->get();
            if (count($geos)) {
                foreach ($geos as $g) {
                    switch ($g->type) {
                        case 'tinh':
                            $model->address_city_display = $g->name;
                            break;
                        case 'huyen':
                            $model->address_district_display = $g->name;
                            break;
                        case 'phuong':
                            $model->address_ward_display = $g->name;

                    }
                }
            }
        });
    }

    public function save_project($data)
    {
        $res = new Project();
        foreach ($data as $key => $val) {
            $val = Common::standardize_model_value_type($val, $key);
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false) {
                $res->{$key} = $res->generateSlug($val, 'seo_alias');
            } else if ($key == 'title') {
                $res->{$key} = $val;
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            } else
                $res->{$key} = $val;
        }
        if ($res->save()) {
//            $services = $data['services'];
//            foreach ($services as $value) {
//                $service['service_id']      = $value['service_id'];
//                $service['service_value']   = $value['value'];
//                $service['project_id']      = $res->_id;
//                ProjectServiceValue::forceCreate($service);
//            }

        }
        return $res->_id;
    }

    public function update_project($data, $id)
    {
        $res = Project::find($id);
        foreach ($data as $key => $val) {
            $val = Common::standardize_model_value_type($val, $key);
            if ($key == 'seo_alias' && $val != "" && $val != null && $val != false) {
                $res->seo_alias = $res->generateSlug($val, 'seo_alias');
            } else
                $res->{$key} = $val;
        }
        return $res->update();
    }

    public function list($request)
    {
        $get_own_only = isset($request['own']) ? $request['own'] : false;
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        $projection = isset($request['projection']) ? $request['projection'] : '';
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $list = Project::where('deleted', '!=', 1);

//        if ($keyword) {
//            $list->where('title', 'regexp', '/.*' . $keyword . '/i');
//        }

        if (is_array($ids) && count($ids)) {
            $list->whereIn('_id', $ids);
        }

        if ($request) {
            foreach ($request as $k => $v) {
                if (!in_array($k, ['ids', 'limit', 'keyword', 'start_date', 'end_date', 'page', 'projection', 'api_token', 'own', 'city', 'range_1'])) {
                    if ($v == 'false' || $v == 'true')
                        $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                    $list->where(function ($query) use ($k, $v) {
                        $query->orWhere($k, $v)
                            ->orWhere(str_replace('_', '.', $k), $v);
                    });
                }
            }
        }

        if (isset($request['keyword']) && $keyword) {
            $keyword = $request['keyword'];
            $list->where(function ($query) use ($keyword) {
                $query->where('title', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('short_title', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('address_1', 'LIKE', '%' . $keyword . '%');
            });
        }

        if (isset($request['city'])) {
            $city = Geo::getCityBySlug($request['city']);
            if ($city) {
                $list->where('address_city', intval($city->code));
            }
        }

        if ($get_own_only) {
            JWTAuth::parseToken();
            $token = JWTAuth::getToken();
            if (!$token) return [];
            $user = JWTAuth::toUser($token);
            $list->where('user_id', '=', $user->_id);
        }

        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (!empty($projection)) {
            $projection = explode(',', $projection);
            $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit), $projection);
        } else {
            $response = $list->orderBy('updated_at', 'desc')->paginate(intval($limit));
        }


        return $response->toArray();
    }

    public static function getById($id)
    {
        return Project::find($id);
    }

    public static function getProjectBySlug($slug)
    {
        $_project = Project::where('seo_alias', $slug)->first();

        return $_project;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function updateDeleted($id)
    {
        $project = self::find($id)->update([
            'deleted' => 1
        ]);

        return $project;
    }

}
