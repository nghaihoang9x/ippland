<?php

namespace App\Listeners;

use App\Events\ShippingStatusOrderChanged;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class EventShippingStatusChanged
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
     * @param  ShippingStatusOrderChanged  $event
     * @return void
     */
    public function handle(ShippingStatusOrderChanged $event)
    {
        try{
            Mail::to($event->order->customer->email)->queue(new \App\Mail\OrderShipping($event->order, $event->status));
            $orderLog = new Logger('shipping_email');
            $orderLog->pushHandler(new StreamHandler(storage_path('logs/order_shipping_email_send.log')));
            $orderLog->info('EmailLog', array(
                'email' => $event->order->customer->email,
                'full_name' => $event->order->customer->fullname
            ));
        }catch (\Exception $ex){

        }
    }
}
