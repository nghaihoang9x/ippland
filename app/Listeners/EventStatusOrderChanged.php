<?php

namespace App\Listeners;

use App\Events\StatusOrderChanged;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class EventStatusOrderChanged
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
     * @param  StatusOrderChanged  $event
     * @return void
     */
    public function handle(StatusOrderChanged $event)
    {
        try{
            Mail::to($event->order->customer->email)->queue(new \App\Mail\Orders($event->order, $event->status));
            $orderLog = new Logger('order_email');
            $orderLog->pushHandler(new StreamHandler(storage_path('logs/orders_email_send.log')));
            $orderLog->info('EmailLog', array(
                'email' => $event->order->customer->email,
                'full_name' => $event->order->customer->fullname
            ));
        }catch (\Exception $ex){

        }
    }
}
