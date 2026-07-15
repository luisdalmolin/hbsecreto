<?php

namespace App\Notifications\ExpoPush;

interface ExpoPushTransport
{
    public function send(ExpoPushMessage ...$messages): ExpoPushResult;
}
