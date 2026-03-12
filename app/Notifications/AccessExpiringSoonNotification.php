<?php
namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AccessExpiringSoonNotification extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Report Access Expiring Soon – VinCheck')
            ->greeting("Hello {$notifiable->name}!")
            ->line('Your access to a vehicle report is expiring in less than 2 hours.')
            ->line("Vehicle: {$this->payment->vehicle->plate_number}")
            ->line("Access expires at: {$this->payment->expires_at->format('Y-m-d H:i')}")
            ->action('Download Now', url("/api/reports/{$this->payment->report?->id}/download"))
            ->line('Download it before it expires!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'access_expiring_soon',
            'message'    => "Your report access for vehicle {$this->payment->vehicle->plate_number} expires soon.",
            'payment_id' => $this->payment->id,
            'vehicle_id' => $this->payment->vehicle_id,
            'expires_at' => $this->payment->expires_at,
            'report_id'  => $this->payment->report?->id,
        ];
    }
}
