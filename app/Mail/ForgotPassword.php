<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ForgotPassword extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if(isset($this->data->locale) && $this->data->locale == 'en'){
            $subject = 'Jillian – Password Recovery';
        }else{
            $subject = 'Jillian – Lấy lại mật khẩu đăng nhập';
        }

        return $this->from(ENV('MAIL_USERNAME'), 'Jillian')
            ->subject($subject)
            ->view('emails.forgot_password')
            ->with(['forgot_link' =>  isset($this->data->fullname) ? ENV('APP_URL').'/account/password/'.$this->data->forgot_code : '',
                'name' => isset($this->data->fullname) ? $this->data->fullname : '',
                'locale' => isset($this->data->locale) ? $this->data->locale : 'vi',
            ]);
    }

    public function failed()
    {
        $data    = $this->data;
        $orderLog = new Logger('customer_create');
        $orderLog->pushHandler(new StreamHandler(storage_path('logs/forgot_email_fail.log')));

        $orderLog->info('EmailLog', array(
            'email' => $data->email,
            'full_name' => $data->fullname,
        ));

        //add order transaction
        sleep(5);

    }
}
