<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('ketemupens:expire')->hourly();

// PII penerima dibersihkan harian setelah masa retensi (default 90 hari).
Schedule::command('ketemupens:purge-pii')->daily();
