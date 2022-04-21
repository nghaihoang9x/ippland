<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class OrderCreate extends Mailable implements ShouldQueue
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
            ->bcc(ENV('MAIL_BCC', 'phunx@site4com.net'))
            ->subject('Thông tin đơn hàng trên JIL')
            ->view('emails.order_create')
            ->with([
                'order' => $this->data,
                ]);
    }

    public function failed()
    {
        $data    = $this->data;
        $orderLog = new Logger('order_create');
        $orderLog->pushHandler(new StreamHandler(storage_path('logs/order_create_email_send_fail.log')));

        $orderLog->info('EmailLog', array(
            'email' => $data->customer->email,
            'full_name' => $data->customer->fullname,
        ));

        //add order transaction
        sleep(5);

    }
}
