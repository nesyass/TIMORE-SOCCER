<?php

use App\Models\AdminRecord;
use Illuminate\Support\Facades\Schedule;

Schedule::call(fn () => AdminRecord::expireRegistrations())
    ->name('expire-student-registrations')->everyMinute();

Schedule::call(fn () => AdminRecord::expireTeamRegistrations())
    ->name('expire-team-registrations')->everyMinute();
