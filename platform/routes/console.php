<?php

use App\Models\Proposal;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('proposals:expire', function () {
    $count = Proposal::whereIn('status', ['draft', 'sent', 'viewed'])
        ->whereNotNull('expires_at')->where('expires_at', '<', now())
        ->get()->filter->expireIfNeeded()->count();

    $this->info("Expired {$count} proposal(s).");
})->purpose('Mark proposals past their validity date as expired');

Schedule::command('proposals:expire')->hourly();
