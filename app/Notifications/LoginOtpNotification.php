<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class LoginOtpNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly Carbon $expiresAt,
    ) {
        $this->onQueue('mail');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode OTP Login DentalCare')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Gunakan kode OTP berikut untuk menyelesaikan login Anda:')
            ->line($this->code)
            ->line('Kode ini berlaku sampai '.$this->expiresAt->timezone(config('app.timezone'))->format('d M Y H:i').' WIB.')
            ->line('Jika Anda tidak mencoba login, abaikan email ini dan segera ganti password akun Anda.');
    }
}
