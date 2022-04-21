<?php
$url_icon = ENV('MEDIA_URL') . '/image/December2019/tick2x.png';
?>
@extends('layouts.main')
@section('content')
    <?php
    if (isset($order->note) && !empty($order->note)) {
        $note_ = explode(',', $order->note);
        $lydo = isset($note_[1]) ? trim(str_replace('Lý do:', '', $note_[1])) : '';
        $ghichu = isset($note_[2]) ? trim(str_replace('ghi chú:', '', $note_[2])) : '';
    }
    ?>
    @if(isset($order->locale) && $order->locale == 'en')
        <div style="padding-bottom: 0px;">
        <div style="width: 100%; margin-left: auto; margin-right: auto;">
            <div style="text-align: center;">
                <h2 style="font-size: 28px; font-weight: bold; text-align: center; color: #151515; margin-top: 50px; margin-bottom: 15px;">
                    Jillian – Order cancellation
                </h2>
            </div>
            <div style="margin-top: 25px;">
                <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515;">
                    Dear <span style="font-weight: 700;">{{ isset($order->customer->fullname) ? $order->customer->fullname : 'GUEST' }}!</span>
                </div>
                @if(isset($lydo) && !empty($lydo))
                <div style="text-align: left; margin-bottom: 10px;margin-top: 10px;font-size: 16px;color: #000; ">
                    Your order has been canceled for the following reason: {{$lydo}}
                </div>
                @endif
                <div style="text-align: left; text-transform: uppercase; margin-bottom: 25px;font-size: 16px;font-weight: 500;color: #000; ">
                    HERE IS YOUR ORDER INFORMATION:
                </div>
                <div style="padding: 0;">
                    <div style="position: relative; margin-bottom: 20px;">
                        <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                            <img src="{{$url_icon}}" width="12"/> Status: <span
                                    style="font-weight: 700">Order has been canceled</span></div>
                    </div>
                    @if(isset($lydo) && !empty($lydo))
                        {{--<div style="position: relative; margin-bottom: 20px;">--}}
                            {{--<div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">--}}
                                {{--<img src="{{$url_icon}}" width="12"/> Lý do: <span--}}
                                        {{--style="font-weight: 700">{{$lydo}}</span></div>--}}
                        {{--</div>--}}
                    @endif
                    @if(isset($ghichu) && !empty($ghichu))
                        <div style="position: relative; margin-bottom: 20px;">
                            <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                                <img src="{{$url_icon}}" width="12"/> Notes: <span
                                        style="font-weight: 700">{{$ghichu}}</span></div>
                        </div>
                    @endif
                    <div style="position: relative; margin-bottom: 20px;">
                        <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                            <img src="{{$url_icon}}" width="12"/> Order number: <span
                                    style="font-weight: 700">{{ isset($order->title) ? $order->title : '000000' }}</span>
                        </div>
                    </div>
                    <div style="position: relative; margin-bottom: 20px;">
                        <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                            <img src="{{$url_icon}}" width="12"/> Order details:
                        </div>
                        <div style="margin-top: 10px; padding-left: 17px;">
                            <table style="width: 100%; border-radius: 10px; overflow: hidden; background-color: #f5f5f5; font-weight: bold;font-size: 16px;" cellpadding="15" cellspacing="0">
                                <tr>
                                    <td style="font-size: 16px;width: 45%;">
                                        Product
                                    </td>
                                    <td style="font-size: 16px;font-weight: normal; height: 25%;text-align: center; background-color: #fafafa;">
                                        Quantity
                                    </td>
                                    <td style="font-size: 16px;font-weight: normal; height: 35%;text-align: center;">Volume</td>
                                </tr>
                                @if (isset($order->line_items) && $order->line_items)
                                    @foreach ($order->line_items as $item)
                                        <tr>
                                            <td style="font-size: 16px;width: 45%;" {{!$loop->last ? ' style="border-bottom: #eee 1px solid"' : ''}}>{{$item->product_title}}</td>
                                            <td style="font-size: 16px;font-weight: normal; height: 25%;text-align: center; background-color: #fafafa;{{!$loop->last ? ' border-bottom: #eee 1px solid;' : ''}}">{{$item->quantity}}</td>
                                            <td style="font-size: 16px;font-weight: normal; height: 35%;text-align: center;{{!$loop->last ? ' border-bottom: #eee 1px solid;' : ''}}">{{isset($item->variant_title) ? $item->variant_title : (isset($item->title) ? $item->title : '')}}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </table>
                        </div>
                    </div>
                    <div style="position: relative; margin-bottom: 20px;">
                        <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;"><img src="{{$url_icon}}" width="12" /> Total payment: <span style="font-weight: 700">{{ isset($order->total_price) ? \App\Helpers\Common::money_format($order->total_price) : 0 }}</span></div>
                    </div>
                    <div style="position: relative; margin-bottom: 20px;">
                        <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;"><img src="{{$url_icon}}" width="12" /> Payment method: <span style="font-weight: 700">{{ isset($order->payment_method) ? $order->payment_method : 'COD'}}</span></div>
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
                        Jillian - Đơn hàng bị huỷ
                    </h2>
                </div>
                <div style="margin-top: 25px;">
                    <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515;">
                        Thân gửi <span style="font-weight: 700;">{{ isset($order->customer->fullname) ? $order->customer->fullname : 'GUEST' }}!</span>
                    </div>
                    @if(isset($lydo) && !empty($lydo))
                        <div style="text-align: left; margin-bottom: 10px;margin-top: 10px;font-size: 16px;color: #000; ">
                            Đơn hàng của bạn đã bị hủy vì lí do: {{$lydo}}
                        </div>
                    @endif
                    <div style="text-align: left; text-transform: uppercase; margin-bottom: 25px;font-size: 16px;font-weight: 500;color: #000; ">
                        Dưới đây là thông tin về đơn hàng của bạn:
                    </div>
                    <div style="padding: 0;">
                        <div style="position: relative; margin-bottom: 20px;">
                            <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                                <img src="{{$url_icon}}" width="12"/> Tình trạng: <span
                                        style="font-weight: 700">Đơn hàng bị huỷ</span></div>
                        </div>
                        @if(isset($lydo) && !empty($lydo))
                            {{--<div style="position: relative; margin-bottom: 20px;">--}}
                            {{--<div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">--}}
                            {{--<img src="{{$url_icon}}" width="12"/> Lý do: <span--}}
                            {{--style="font-weight: 700">{{$lydo}}</span></div>--}}
                            {{--</div>--}}
                        @endif
                        @if(isset($ghichu) && !empty($ghichu))
                            <div style="position: relative; margin-bottom: 20px;">
                                <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                                    <img src="{{$url_icon}}" width="12"/> Ghi chú: <span
                                            style="font-weight: 700">{{$ghichu}}</span></div>
                            </div>
                        @endif
                        <div style="position: relative; margin-bottom: 20px;">
                            <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                                <img src="{{$url_icon}}" width="12"/> Mã đơn hàng: <span
                                        style="font-weight: 700">{{ isset($order->title) ? $order->title : '000000' }}</span>
                            </div>
                        </div>
                        <div style="position: relative; margin-bottom: 20px;">
                            <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                                <img src="{{$url_icon}}" width="12"/> Thông tin sản phẩm:
                            </div>
                            <div style="margin-top: 10px; padding-left: 17px;">
                                <table style="width: 100%; border-radius: 10px; overflow: hidden; background-color: #f5f5f5; font-weight: bold;font-size: 16px;"
                                       cellpadding="15" cellspacing="0">
                                    <tr>
                                        <td style="font-size: 16px;width: 45%;border-bottom: #eee 1px solid;">
                                            Tên sản phẩm
                                        </td>
                                        <td style="font-size: 16px;font-weight: normal; height: 25%;text-align: center; background-color: #fafafa;border-bottom: #eee 1px solid;">
                                            Số lượng
                                        </td>
                                        <td style="font-size: 16px;font-weight: normal; height: 35%;text-align: center;border-bottom: #eee 1px solid;">Dung tích</td>
                                    </tr>
                                    @if (isset($order->line_items) && $order->line_items)
                                        @foreach ($order->line_items as $item)
                                            <tr>
                                                <td style="font-size: 16px;width: 45%;" {{!$loop->last ? ' style="border-bottom: #eee 1px solid"' : ''}}>
                                                    {{$item->product_title}}
                                                </td>
                                                <td style="font-size: 16px;font-weight: normal; height: 25%;text-align: center; background-color: #fafafa;{{!$loop->last ? ' border-bottom: #eee 1px solid;' : ''}}">{{$item->quantity}}
                                                </td>
                                                <td style="font-size: 16px;font-weight: normal; height: 35%;text-align: center;{{!$loop->last ? ' border-bottom: #eee 1px solid;' : ''}}">{{isset($item->variant_title) ? $item->variant_title : (isset($item->title) ? $item->title : '')}}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </table>
                            </div>
                        </div>
                        <div style="position: relative; margin-bottom: 20px;">
                            <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                                <img src="{{$url_icon}}" width="12"/> Tổng thanh toán: <span
                                        style="font-weight: 700">{{ isset($order->total_price) ? \App\Helpers\Common::money_format($order->total_price) : 0 }}</span>
                            </div>
                        </div>
                        <div style="position: relative; margin-bottom: 20px;">
                            <div style="font-size: 16px; letter-spacing: 0.32px; text-align: left; color: #151515; position: relative;">
                                <img src="{{$url_icon}}" width="12"/> Hình thức thanh toán:
                                <span style="font-weight: 700">{{ isset($order->payment_method) ? $order->payment_method : 'COD'}}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
