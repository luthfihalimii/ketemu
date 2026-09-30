<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('ketemupens:expire')->hourly();
