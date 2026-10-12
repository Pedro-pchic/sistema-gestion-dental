<?php

return [
    'email' => [
        'enabled' => env('APPOINTMENT_EMAIL_REMINDERS_ENABLED', false),
        'hours_before' => (int) env('APPOINTMENT_REMINDER_HOURS_BEFORE', 24),
    ],

    'retry' => [
        'max_attempts' => 3,
        'delays_minutes' => [15, 60],
        'claim_timeout_minutes' => 15,
    ],

    'sms' => [
        'enabled' => env('APPOINTMENT_SMS_REMINDERS_ENABLED', false),
        'driver' => env('APPOINTMENT_SMS_DRIVER', 'disabled'),
    ],
];
