<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class StaffCreate extends Mailable implements ShouldQueue
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
        return $this->from(ENV('MAIL_USERNAME'), 'Jillian')
            ->subject('Jillian – Thông tin tài khoản thành viên')
            ->view('emails.staff_create')
            ->with([
                'name' => isset($this->data->fullname) ? $this->data->fullname : '',
                'username' => isset($this->data->email) ? $this->data->email : '',
                'password_link' =>  ENV('SITE_URL').'/get-password/'.(isset($this->data->role) ? $this->data->role.'/' : '').(isset($this->data->token_create) ? $this->data->token_create : ''),
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
