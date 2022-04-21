<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Auth\User as Authenticatable;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;

class Staff extends Authenticatable {

    use Notifiable;
    protected $guard = 'staff';
    protected $connection = 'mongodb';
    protected $collection = 'staffs';

    protected $fillable = ['email', 'password', 'phone', 'fullname', 'gender', 'birthday'];

    protected $hidden = ['password'];

    public static $ATTR = [];

    public function validateSave(){
        return true;
    }

    public function saveStaff($data){
        $res = new Staff();
        foreach ($data as $key => $val) {
            $res->{$key} = $val;
        }
        $res->save();
        return $res->_id;
    }

    public function updateStaff($data, $id){
        $res = Staff::find($id);

        if (!empty($data['password']))
        {
            $data['password'] = Hash::make($data['password']);
        } else {
            $data['password'] = $res->password;
        }
        unset($data['user_id']);
        unset($data['old_password']);
        unset($data['re_password']);

        foreach ($data as $key => $val) {
            $res->{$key} = $val;
        }
        return $res->update();
    }

    public function listStaff($request) {
        $ids = isset($request['ids']) ? $request['ids'] : '';
        $limit = isset($request['limit']) ? $request['limit'] : '';
        $keyword = isset($request['keyword']) ? $request['keyword'] : '';
        if ($ids) {
            $ids = explode(',', $ids);
        }

        $list = Staff::where('deleted', '!=', 1);
        if ($keyword) {
            $list->where('fullname', 'regexp', '/.*'.$keyword.'/i');
        }

        $limit = $limit != '' ? $limit : env('PAGINATION');

        if (is_array($ids) && count($ids)) {
            $list = $list->whereIn('_id', $ids);
        }
        if($request){
            unset($request['options']);
            unset($request['device_token']);
            unset($request['carts']);
            foreach ($request as $k => $v){
                if(!in_array($k, ['ids', 'limit', 'keyword', 'start_date', 'end_date', 'page', 'type', 'options', 'device_token', 'carts', 'query', 'discounts_code', 'plan_id', 'work_shift', 'buyer_type', 'api_token'])){
                    if($v == 'false' || $v == 'true')
                        $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                    $list->where($k, $v);
                }
            }
        }
        $response = $list->orderBy('updated_at')->paginate(intval($limit));

        return $response->toArray();
    }

    public function listStaffByIds($ids) {
        if (count($ids)) {
            $res = [];
            if (is_array($ids) && count($ids)) {
                $categories = Staff::whereIn('id', $ids)->get();
                $i = 0;
                foreach ($categories as $_Staff) {
                    $res[$i]['label'] = $_Staff->title;
                    $res[$i]['value'] = $_Staff->_id;
                    $i++;
                }
            }


            return $res;
        }
    }

    public static function createUser($credentials){
        $user = self::forceCreate($credentials);
        $user->id = $user->_id;
        $user->password = Hash::make($credentials['password']);
        $user->activation_code = hash_hmac('sha256', str_random(40), $credentials['password']);

        return $user->save()?$user:false;
    }

    public static function checkToken(){
        \Config::set( 'jwt.user', Staff::class );
        \Config::set( 'auth.providers.users.model', Staff::class );
        try {
            JWTAuth::parseToken();
            $token = JWTAuth::getToken();
            $user = JWTAuth::authenticate();
            return $user;
        } catch (TokenExpiredException $e) {
            return 'expired';
        } catch (JWTException $e){
            return false;
        } catch(\Exceptions $e){
            return false;
        }
    }

    public function roles()
    {
        return $this->hasOne('App\Models\Role', 'slug', 'role');
    }

    /**
     * Checks if User has access to $permissions.
     */
    public function hasAccess(array $permissions) : bool
    {
        // check if the permission is available in any role
        if($this->roles->hasAccess($permissions)) {
            return true;
        }
        return false;
    }
}