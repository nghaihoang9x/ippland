
@extends('layouts.main')
@section('content')
    @if(isset($locale) && $locale == 'en')
    <div style="padding-bottom: 0px;">
        <div style="width: 100%; margin-left: auto; margin-right: auto;">
            <div style="text-align: center;">
                <h2 style="font-size: 28px; font-weight: bold; text-align: center; color: #151515; margin-top: 50px; margin-bottom: 15px;">
                    Jillian – Password recovery
                </h2>
                <div style="font-size: 16px; letter-spacing: 0.32px; text-align: center; color: #151515;">
                    Dear <span style="font-weight: 700;">{{ $name }}!</span>
                </div>
            </div>
            <div style="margin-top: 25px;">
                <div style="padding: 0 20px">
                    <div style="position: relative; margin-bottom: 20px;">
                        <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                            We received your password reset request at Jillianperfume.com. If you did not request, please ignore this email.<br>
                            To reset your password, please click the link below or copy the link into your browser and follow the instruction:<br>
                            <a href="{{ $forgot_link }}">{{ $forgot_link }}</a>
                        </div>
                    </div>
                    <div style="position: relative; margin-bottom: 20px;">
                        <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                            Jillian is always ready to support customers in the most timely manner, our Customer Care Service operates from 9am to 5.30pm daily including weekends.<br><br>Please contact us for your questions and enquiries.
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
                        Jillian - Lấy lại mật khẩu
                    </h2>
                    <div style="font-size: 16px; letter-spacing: 0.32px; text-align: center; color: #151515;">
                        Thân gửi <span style="font-weight: 700;">{{ $name }}!</span>
                    </div>
                </div>
                <div style="margin-top: 25px;">
                    <div style="padding: 0 20px">
                        <div style="position: relative; margin-bottom: 20px;">
                            <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                                Chúng tôi nhận được yêu cầu đặt lại mật khẩu của bạn tại website Jillianperfume.com. Nếu bạn không yêu cầu, vui lòng bỏ qua email này.<br>
                                Để đặt lại mật khẩu, click ngay hoặc copy đường link này vào trình duyệt của bạn và làm theo hướng dẫn:<br>
                                <a href="{{ $forgot_link }}">{{ $forgot_link }}</a>
                            </div>
                        </div>
                        <div style="position: relative; margin-bottom: 20px;">
                            <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                                Jillian luôn sẵn sàng để hỗ trợ khách hàng kịp thời, dịch vụ CSKH của chúng tôi
                                hoạt động từ 9h đến 17h30 hằng ngày kể cả cuối tuần. Vui lòng liên hệ để được giải
                                đáp mọi thắc mắc của bạn!<br><br>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
