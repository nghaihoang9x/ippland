<?php

namespace App\Listeners;

use App\Events\PaymentStatusOrderChanged;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class EventPaymentStatusChanged
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  PaymentStatusOrderChanged  $event
     * @return void
     */
    public function handle(PaymentStatusOrderChanged $event)
    {
        try{
            Mail::to($event->order->customer->email)->queue(new \App\Mail\OrderPayment($event->order));
            $orderLog = new Logger('order_payment_email');
            $orderLog->pushHandler(new StreamHandler(storage_path('logs/order_payment_email_send.log')));
            $orderLog->info('EmailLog', array(
                'email' => $event->order->customer->email,
                'full_name' => $event->order->customer->fullname
            ));
        }catch (\Exception $ex){

        }
    }
}
