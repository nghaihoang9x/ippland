<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class OrderShipping extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $data;
    protected $status;

    public function __construct($data, $status)
    {
        $this->data = $data;
        $this->status = $status;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {


        switch ($this->status){
            case 'completed':
                $view = 'emails.order_complete';
                if(isset($this->data->locale) && $this->data->locale == 'en'){
                    $subject = 'Jillian – Delivery Successful #' . $this->data->title;
                }else {
                    $subject = 'Jillian – Đơn hàng giao thành công #' . $this->data->title;
                }
                break;
            case 'shipping':
            default:
                $view = 'emails.shipping';
            if(isset($this->data->locale) && $this->data->locale == 'en'){
                $subject = 'Jillian – Your order has been shipped #' . $this->data->title;
            }else {
                $subject = 'Jillian – Đơn hàng đang được giao #' . $this->data->title;
            }
                break;
        }

        return $this->from(ENV('MAIL_USERNAME'), 'Jillian')
            ->bcc('info@jillianperfume.com', 'Jillian')
            ->subject($subject)
            ->view($view)
            ->with([
                'order' => $this->data,
                ]);
    }

    public function failed($exception)
    {
        $data    = $this->data;
        $orderLog = new Logger('order_shipping');
        $orderLog->pushHandler(new StreamHandler(storage_path('logs/order_shipping_email_send_fail.log')));

        $orderLog->info('EmailLog', ['log' => $exception->getMessage()]);
        $orderLog->info('EmailLog', array(
            'email' => $data->customer->email,
            'full_name' => $data->customer->fullname,
        ));

        //add order transaction
//        sleep(5);

    }
}
