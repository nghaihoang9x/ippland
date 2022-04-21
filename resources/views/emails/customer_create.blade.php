@extends('layouts.main')
@section('content')
    @if(isset($locale) && $locale == 'en')
    <div style="padding-bottom: 0px;">
        <div style="width: 100%; margin-left: auto; margin-right: auto;">
            <div style="text-align: center;">
                <h2 style="font-size: 28px; font-weight: bold; text-align: center; color: #151515; margin-top: 50px; margin-bottom: 15px;">
                    Jillian – Account Registration Successful
                </h2>
                <div style="font-size: 16px; letter-spacing: 0.32px; text-align: center; color: #151515;">
                    Dear <span style="font-weight: 700;">{{ $name }}!</span>
                </div>
            </div>
            <div style="margin-top: 25px;">
                <div style="text-align: left; margin-bottom: 10px;margin-top: 10px;font-size: 16px;color: #000; ">
                    Thank you for your account registration!
                </div>
                <div style="text-align: left; text-transform: uppercase; margin-bottom: 25px;font-size: 16px;font-weight: 500;color: #000; ">
                    HERE IS YOUR ACCOUNT INFORMATION:
                </div>
                <div style="padding: 0px">
                    <div style="position: relative; margin-bottom: 20px;">
                        <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                            Username: <span
                                    style="font-weight: 700"> {{$username}}</span></div>
                    </div>
                    <div style="position: relative; margin-bottom: 20px;">
                        <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                            Password: <span
                                    style="font-weight: 700">{{$password}}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
        <div style="padding-bottom: 0px;">
            <div style="width: 100%; margin-left: auto; margin-right: auto;">
                <div style="text-align: center;">
                    <h2 style="font-size: 28px; font-weight: bold; text-align: center; color: #151515; margin-top: 50px; margin-bottom: 15px;">
                        Jillian - Đăng ký thành công
                    </h2>
                    <div style="font-size: 16px; letter-spacing: 0.32px; text-align: center; color: #151515;">
                        Thâng gửi <span style="font-weight: 700;">{{ $name }}!</span>
                    </div>
                </div>
                <div style="margin-top: 25px;">
                    <div style="text-align: left; margin-bottom: 10px;margin-top: 10px;font-size: 16px;color: #000; ">
                        Cảm ơn bạn đã đăng ký tài khoản mua hàng trên website Jillianperfume.com
                    </div>
                    <div style="text-align: left; text-transform: uppercase; margin-bottom: 25px;font-size: 16px;font-weight: 500;color: #000; ">
                        Thông tin tài khoản:
                    </div>
                    <div style="padding: 0px">
                        <div style="position: relative; margin-bottom: 20px;">
                            <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                                Username (Tên đăng nhập): <span
                                        style="font-weight: 700"> {{$username}}</span></div>
                        </div>
                        <div style="position: relative; margin-bottom: 20px;">
                            <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                                Password (Mật khẩu): <span
                                        style="font-weight: 700">{{$password}}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
