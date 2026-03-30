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

Artisan::command('profile-import:microsoft', function (MicrosoftProfileSyncService $service) {
    $summary = $service->importAllMicrosoftUsers();

    $this->info("Microsoft import complete. Total: {$summary['total']} | Created: {$summary['created']} | Updated: {$summary['updated']} | Skipped: {$summary['skipped']} | Failed: {$summary['failed']}");

    if (! empty($summary['message'])) {
        $this->warn($summary['message']);
    }
})->purpose('Import and update tenant users from Microsoft Graph');
