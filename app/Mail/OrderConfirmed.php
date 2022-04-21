<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class OrderConfirmed extends Mailable implements ShouldQueue
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
            ->subject('Xác nhận thông tin đơn hàng trên Jillian')
            ->view('emails.order_confirm')
            ->with([
                'order' => $this->data,
                ]);
    }

    public function failed()
    {
        $data    = $this->data;
        $orderLog = new Logger('order_confirm');
        $orderLog->pushHandler(new StreamHandler(storage_path('logs/order_confirm_email_send_fail.log')));

        $orderLog->info('EmailLog', array(
            'email' => $data->customer->email,
            'full_name' => $data->customer->fullname,
        ));

        //add order transaction
        sleep(5);

    }
}
