<?php

namespace App\Listeners;

use App\Services\UserLoginActivityLogger;
use Illuminate\Auth\Events\Logout;

class RecordUserLogout
{
    protected $logger;

    public function __construct(UserLoginActivityLogger $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Logout $event)
    {
        $this->logger->recordLogout(request(), $event->user);
    }
}
