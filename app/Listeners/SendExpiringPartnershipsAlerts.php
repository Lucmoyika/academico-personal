<?php

namespace App\Listeners;

use App\Events\ExpiringPartnershipsEvent;
use App\Mail\ExpiringPartnershipAlert;
use App\Traits\ReportsErrors;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class SendExpiringPartnershipsAlerts
{
    use ReportsErrors;

    public function handle(ExpiringPartnershipsEvent $event): void
    {
        foreach ($event->partners as $partner) {
            try {
                Mail::to(config('settings.secretary_email'))->queue(new ExpiringPartnershipAlert($partner));

                $partner->last_alert_sent_at = Carbon::now()->format('Y-m-d');
                $partner->save();
            } catch (\Throwable $e) {
                $this->reportError($e, 'SendExpiringPartnershipsAlerts::handle', [
                    'partner_id' => $partner->id,
                ]);
            }
        }
    }
}
