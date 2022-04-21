<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class CustomerCreate extends Mailable implements ShouldQueue
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
            $subject = 'Jillian – Account Registration Successful';
        }else{
            $subject = 'Jillian – Thông tin tài khoản thành viên';
        }

        return $this->from(ENV('MAIL_USERNAME'), 'Jillian')
            ->subject($subject)
            ->view('emails.customer_create')
            ->with([
                'locale' => isset($this->data->locale) ? $this->data->locale : 'vi',
                'name' => isset($this->data->fullname) ? $this->data->fullname : '',
                'username' => isset($this->data->email) ? $this->data->email : '',
                'password' => isset($this->data->pass) ? $this->data->pass : '********',
                'password_link' =>  ENV('APP_URL').'/get-password/customer/'.(isset($this->data->token_create) ? $this->data->token_create : ''),
                ]);
    }

    public function failed()
    {
        $data    = $this->data;
        $orderLog = new Logger('customer_create');
        $orderLog->pushHandler(new StreamHandler(storage_path('logs/customer_email_fail.log')));

        $orderLog->info('EmailLog', array(
            'email' => $data->email,
            'full_name' => $data->fullname,
        ));

        //add order transaction
        sleep(5);

    }
}
