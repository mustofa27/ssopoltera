<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Support\MicrosoftProfileSyncService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('profile-sync:microsoft', function (MicrosoftProfileSyncService $service) {
    $summary = $service->syncAllMicrosoftUsers();

    $this->info("Profile synchronization complete. Total: {$summary['total']} | Success: {$summary['success']} | Failed: {$summary['failed']}");
})->purpose('Synchronize Microsoft-linked user profiles from Microsoft Graph');
