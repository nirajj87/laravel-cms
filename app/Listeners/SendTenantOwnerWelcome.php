<?php

namespace App\Listeners;

use App\Events\TenantProvisioned;
use App\Notifications\TenantOwnerWelcome;

class SendTenantOwnerWelcome
{
    public function handle(TenantProvisioned $event): void
    {
        $event->owner->notify(new TenantOwnerWelcome($event->tenant));
    }
}
