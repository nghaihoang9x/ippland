<?php
$url_frontend = ENV('APP_URL');
$url_logo = ENV('MEDIA_URL').'/image/December2019/logo_mail_55.png';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Jillian Email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
</head>
<body style="margin: 0; padding: 0;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
        <thead>
            <tr>
                <td style="border-bottom: 1px solid #f5f5f5; padding: 10px 0; text-align: center"><img src="{{$url_logo}}" alt="" width="112"></td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 0 20px;">@yield('content')</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td style="text-align: center" align="center">
                    {!! isset($settings->email_footer) ? $settings->email_footer : '' !!}
                </td>
            </tr>
        </tfoot>
    </table>
</body>
</html>