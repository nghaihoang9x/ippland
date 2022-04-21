<?php

namespace App\Listeners;

use App\Events\ForgotPassword;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class EventForgotPassword
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
     * @param  ForgotPassword  $event
     * @return void
     */
    public function handle(ForgotPassword $event)
    {
        try{
            Mail::to($event->user->email)->queue(new \App\Mail\ForgotPassword($event->user));
            $orderLog = new Logger('customer_email');
            $orderLog->pushHandler(new StreamHandler(storage_path('logs/forgot_email_send.log')));
            $orderLog->info('EmailLog', array(
                'email' => $event->user->email,
                'full_name' => $event->user->fullname
            ));
        }catch (\Exception $ex){

        }
    }
}
