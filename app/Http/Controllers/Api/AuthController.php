<?php

namespace App\Http\Controllers\Api;

use App\Events\CustomerCreate;
use App\Events\ForgotPassword;
use DB;
use Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Validator;


class AuthController extends Controller
{
    public function __construct()
    {
    }

    public function facebookLogin(Request $request)
    {
        $credentials = request()->only('uid', 'access_token', 'permissions');
        if ($user = User::where('facebook_uid', '=', $credentials['uid'])->first()) {
            if (User::where('facebook_uid', '=', $credentials['uid'])->update(['facebook_token' => $credentials['access_token']])) {
                $token = JWTAuth::fromUser($user);
                return response()->json(compact('token'));
            } else {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } else
            return response()->json(['error' => 'could_not_create_token'], 500);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password', 'remember_me');

        if ($user = User::where('email', '=', strtolower($credentials['email']))->where('deleted', '!=', 1)->first()) {
            if (!isset($user->activate) || !$user->activate) {

                return response()->json(['errors' => ['password' => ['Tài khoản chưa được kích hoạt']], 'success' => false], 401);
            }
            if (!Hash::check($credentials['password'], $user->password)) {

                return response()->json(['errors' => ['password' => ['Mật khẩu không chính xác']], 'success' => false], 401);
            } else if ($user->deleted == 1) {

                return response()->json(['errors' => ['email' => ['Tài khoản của bạn đã bị xóa']]], 401);
            } else {
                if (isset($credentials['password']) && $credentials['password']) {
                    \Config::set('jwt.ttl', 86400 * 30);
                }
                $user->has_account = true;
                $user->save();
                $token = JWTAuth::fromUser($user);
                $email = isset($user->email) ? strtolower($user->email) : '';
                $phone = isset($user->phone) ? strtolower($user->phone) : '';
                $fullname = isset($user->fullname) ? strtolower($user->fullname) : '';

                $address = isset($user->address) ? $user->address : '';
                $diploma = isset($user->diploma) ? $user->diploma : '';
                $exp = isset($user->exp) ? $user->exp : '';
                $introduce = isset($user->introduce) ? $user->introduce : '';
                $target_need = isset($user->target_need) ? $user->target_need : '';
                $target_product = isset($user->target_product) ? $user->target_product : '';
                $work_place = isset($user->work_place) ? $user->work_place : '';
                $role = isset($user->role) ? $user->role : '';

                return response()->json(compact('token', 'email', 'phone', 'fullname',
                    'address', 'diploma', 'exp', 'introduce', 'target_need', 'target_product', 'work_place', 'role'));
            }
        } else

            return response()->json(['errors' => ['email' => ['Tài khoản không tồn tại']]], 500);
    }

    public function register()
    {
        $credentials = request()->all();
        if (strlen($credentials['password']) < 6) {
            return response()->json(['error' => 'Mật khẩu phải lớn hơn 6 ký tự'], 200);
        }
        if ($user = User::where('email', '=', strtolower($credentials['email']))->where('deleted', '!=', 1)->first()) {
            if (isset($user->from) && $user->from == 'order') {
                return response()->json(['error' => 'account_exist'], 200);
            }
            return response()->json(['error' => 'Email đã tồn tại'], 200);
        } else {
            $user = User::createUser($credentials);
            if ($user) {
                event(new CustomerCreate($user));
                return response()->json(['error' => false, 'user' => $user, 'code' => 200], 200);
            }
            return response()->json(['error' => 'could_not_create_user'], 500);
        }
    }

    public function active($code)
    {
        $user = User::where('activation_code', $code)->where('deleted', '!=', 1)->first();
        $result = false;
        if ($user)
            $result = $user->active();

        return response()->json(compact('result'));
    }

    public function refresh()
    {
        $newToken = JWTAuth::parseToken()->refresh();
        return response()->json(compact('newToken'));
    }

    public function get()
    {
        echo 1;
        die;
        $newToken = JWTAuth::parseToken()->refresh();
        return response()->json(compact('newToken'));
    }

    public function forgot($email)
    {
        if (!$email) {

            return response()->json(['error' => 'Bạn cần nhập email'], 400);
        }
        $get = request()->all();
        $locale = isset($get['locale']) ? $get['locale'] : 'vi';
        if ($user = User::where('email', '=', strtolower($email))->where('deleted', '!=', 1)->first()) {
            $user->forgot_code = hash_hmac('sha256', str_random(40), $email);
            $user->save();
            $user->locale = $locale;
            event(new ForgotPassword($user));

            return response()->json($user, 200);
        }


        return response()->json(['error' => 'Không tồn tại email'], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'forgot_code' => 'required|max:255',
            'password' => 'required|max:255|min:6',
            're_password' => 'required|max:255|min:6|same:password',
        ]);

        if ($validator->fails()) {

            return response()->json('Invalid data', 422);
        }
        $post = $request->all();
        $user = User::where('forgot_code', $post['forgot_code'])->where('deleted', '!=', 1)->first();
        $result = false;
        if ($user) {
            $token = JWTAuth::fromUser($user);
            $user->password = Hash::make($post['password']);
            $user->forgot_code = '';
            $user->save();

            return response()->json($token, 200);
        }


        return response()->json(compact('result'));
    }

    public function redirect($service)
    {
        $installUrl = Socialite::driver($service)->stateless()->redirect()->getTargetUrl();
        return response()->json($installUrl, 200);
    }

    public function callback($service)
    {
        $getInfo = Socialite::driver($service)->stateless()->user();
        $user = $this->createUser($getInfo, $service);
        $token = JWTAuth::fromUser($user);

        $email = isset($user->email) ? strtolower($user->email) : '';
        $phone = isset($user->phone) ? strtolower($user->phone) : '';
        $fullname = isset($user->fullname) && !empty($user->fullname) ? strtolower($user->fullname) : isset($user->name);

        return response()->json(compact('token', 'email', 'phone', 'fullname'));
    }


    protected function createUser($getInfo, $provider)
    {
        $user = User::where('provider_id', $getInfo->id)->first();
        if (!$user) {
            $user_ = [
                'fullname' => $getInfo->name,
                'email' => strtolower($getInfo->email),
                'provider' => $provider,
                'provider_id' => $getInfo->id
            ];

            $user = User::forceCreate($user_);
        }
        return $user;
    }
}
