<?php

namespace App\Enums;

enum AppointmentReminderStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case Superseded = 'superseded';
}
