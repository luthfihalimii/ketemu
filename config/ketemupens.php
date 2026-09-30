<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domain rules
    |--------------------------------------------------------------------------
    |
    | Tunable business parameters. Kept in config (not hard-coded) so the
    | campus can adjust policy without a code change.
    |
    */

    'expiry' => [
        // How long a found item may sit on the shelf before it is expired
        // automatically. Unclaimed items are usually handed to the campus
        // warehouse after this window.
        'stale_after_days' => (int) env('ITEM_STALE_AFTER_DAYS', 60),

        // How long a finder has to confirm the handover to security staff
        // before the report is flagged as needing follow-up. The report is
        // never closed automatically: the item may already be at the post.
        'deposit_reminder_after_days' => (int) env('ITEM_DEPOSIT_REMINDER_AFTER_DAYS', 2),
    ],

    'pickup_code' => [
        // How long a student has to collect an item once a code is issued.
        'validity_minutes' => (int) env('PICKUP_CODE_VALIDITY_MINUTES', 60 * 24 * 3),
    ],

];
