<?php

namespace App\Listeners;

use App\Events\SendResetPasswordLinkEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendResetPasswordLink;

class SendResetPasswordLinkListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SendResetPasswordLinkEvent $event): void
    {
        $email = $event->email;
        $url = $event->url;

        if($email){
            Mail::to($email)->send(new SendResetPasswordLink($url));
        }
    }
}
