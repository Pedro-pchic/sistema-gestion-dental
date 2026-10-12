<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public Appointment $appointment, public string $channel) {}

    /** @return list<class-string|string> */
    public function via(object $notifiable): array
    {
        return $this->channel === 'sms' ? [SmsChannel::class] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $patient = $notifiable instanceof Patient ? $notifiable : $this->appointment->patient;
        $appointmentTime = $this->appointment->starts_at
            ->timezone(config('appointments.timezone'))
            ->locale('es')
            ->translatedFormat('l j \\d\\e F \\d\\e Y, H:i');

        return (new MailMessage)
            ->subject('Recordatorio de su cita odontológica')
            ->greeting('Hola '.$patient->first_name)
            ->line('Le recordamos su cita para el '.$appointmentTime.'.')
            ->line('Si necesita cambiarla, comuníquese con la recepción del consultorio.')
            ->salutation(config('app.name'));
    }

    public function toSms(object $notifiable): string
    {
        $patient = $notifiable instanceof Patient ? $notifiable : $this->appointment->patient;
        $appointmentTime = $this->appointment->starts_at
            ->timezone(config('appointments.timezone'))
            ->locale('es')
            ->translatedFormat('d/m/Y H:i');

        return 'Hola '.$patient->first_name.', le recordamos su cita el '.$appointmentTime.'.';
    }
}
