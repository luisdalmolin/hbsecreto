<?php

namespace App\Notifications\ExpoPush;

interface SendsExpoPush
{
    public function toExpoPush(object $notifiable): ExpoPushContent;
}
