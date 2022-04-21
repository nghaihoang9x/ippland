<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class OrderPayment extends Mailable implements ShouldQueue
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
        if($this->data->email_status) {
            switch ($this->data->email_status) {
                case 'payment_fail':
                    if(isset($this->data->locale) && $this->data->locale == 'en'){
                        $subject = 'Jillian – Order and Payment failed #' . $this->data->title;
                    }else {
                        $subject = 'Jillian - Đặt hàng và thanh toán thất bại #' . $this->data->title;
                    }
                    $view = 'emails.payment_fail';
                    break;
                case 'payment_completed':
                    if(isset($this->data->locale) && $this->data->locale == 'en'){
                        $subject = 'Jillian – Order and Payment Successful #' . $this->data->title;
                    }else {
                        $subject = 'Jillian - Đặt hàng và thanh toán thành công #' . $this->data->title;
                    }
                    $view = 'emails.payment_completed';
                    break;
                case 'payment_success':
                default:
                    if(isset($this->data->locale) && $this->data->locale == 'en'){
                        $subject = 'Jillian – Payment Successful #' . $this->data->title;
                    }else {
                        $subject = 'Jillian - Thanh toán thành công #' . $this->data->title;
                    }
                    $view = 'emails.payment';
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
    }

    public function failed($exception)
    {
        $data    = $this->data;
        $orderLog = new Logger('order_payment');
        $orderLog->pushHandler(new StreamHandler(storage_path('logs/order_payment_email_send_fail.log')));

        $orderLog->info('EmailLog', ['log' => $exception->getMessage()]);
        $orderLog->info('EmailLog', array(
            'email' => $data->customer->email,
            'full_name' => $data->customer->fullname,
        ));

        //add order transaction
//        sleep(5);

    }
}
