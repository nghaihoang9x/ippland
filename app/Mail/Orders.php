<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Orders extends Mailable implements ShouldQueue
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
            case 'confirmed':
                $view = 'emails.order_confirm';
                if(isset($this->data->locale) && $this->data->locale == 'en'){
                    $subject = 'Jillian – Order Confirmation Successful #' . $this->data->title;
                }else{
                    $subject = 'Jillian – Xác nhận đơn hàng thành công #'.$this->data->title;
                }
                break;
            case 'canceled':
                $view = 'emails.order_cancel';
                if(isset($this->data->locale) && $this->data->locale == 'en'){
                    $subject = 'Jillian – Order cancellation #' . $this->data->title;
                }else {
                    $subject = 'Jillian – Đơn hàng bị huỷ #' . $this->data->title;
                }
                break;
            case 'refunded':
                $view = 'emails.order_refund';
                if(isset($this->data->locale) && $this->data->locale == 'en'){
                    $subject = 'Jillian – Refund Successful #' . $this->data->title;
                }else {
                    $subject = 'Jillian – Hoàn tiền thành công #' . $this->data->title;
                }
                break;
            case 'created':
            default:
                $view = 'emails.order_create';
                if(isset($this->data->locale) && $this->data->locale == 'en'){
                    $subject = 'Jillian – Order successful #'. $this->data->title;
                }else {
                    $subject = 'Jillian – Đặt hàng thành công #' . $this->data->title;
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
        $orderLog = new Logger('orders');
        $orderLog->pushHandler(new StreamHandler(storage_path('logs/order_email_send_fail.log')));
        $orderLog->info('EmailLog', ['log' => $exception->getMessage()]);
        $orderLog->info('EmailLog', array(
            'email' => $data->customer->email,
            'full_name' => $data->customer->fullname,
        ));

        //add order transaction
//        sleep(5);

    }
}
