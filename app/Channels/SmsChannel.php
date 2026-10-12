<?php

namespace App\Channels;

use App\Contracts\SmsSender;
use App\Models\Patient;
use App\Notifications\AppointmentReminderNotification;
use Illuminate\Notifications\Notification;
use LogicException;

class SmsChannel
{
    public function __construct(private SmsSender $smsSender) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof Patient || ! $notification instanceof AppointmentReminderNotification) {
            throw new LogicException('The SMS channel only supports patient appointment reminders.');
        }

        if (! $this->smsSender->isAvailable()) {
            throw new LogicException('SMS delivery is unavailable because no provider is configured.');
        }

        $phone = (string) $notifiable->phone;

        if (! preg_match('/^\+?[0-9\s().-]{7,40}$/', $phone)
            || preg_match_all('/[0-9]/', $phone) < 7) {
            throw new LogicException('The patient does not have a valid SMS contact number.');
        }

        $this->smsSender->send($phone, $notification->toSms($notifiable));
    }
}
