<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    $databasePath = database_path('database.sqlite');
    if (! File::exists($databasePath)) {
        return;
    }

    $backupDir = storage_path('backups');
    if (! File::exists($backupDir)) {
        File::makeDirectory($backupDir, 0755, true);
    }

    $filename = 'backup_'.now()->format('Y-m-d_H-i-s').'.sqlite';
    File::copy($databasePath, $backupDir.'/'.$filename);
})->weekly();
