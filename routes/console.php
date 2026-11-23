<?php

use App\Jobs\RefreshInventorySummary;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new RefreshInventorySummary)->dailyAt('01:00')->timezone('Asia/Jakarta');
