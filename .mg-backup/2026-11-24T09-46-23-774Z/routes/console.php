<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('blog:publish-scheduled')->everyMinute()->withoutOverlapping();

Schedule::command('motogrip:backup database')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('motogrip:backup media')->weeklyOn(0, '02:30')->withoutOverlapping();
Schedule::command('backup:clean --disable-notifications')->dailyAt('03:00');
Schedule::command('motogrip:backup-verify')->dailyAt('03:30');

Schedule::command('motogrip:purge-old-data')->weeklyOn(1, '04:00');
Schedule::command('queue:prune-failed --hours=720')->daily();
Schedule::command('model:prune')->daily();
