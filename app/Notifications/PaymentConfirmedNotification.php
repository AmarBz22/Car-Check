<?php
namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentConfirmedNotification extends Notification
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
            ->subject('Payment Confirmed – VinCheck')
            ->greeting("Hello {$notifiable->name}!")
            ->line('Your payment has been confirmed successfully.')
            ->line("Vehicle: {$this->payment->vehicle->plate_number}")
            ->line("Amount: {$this->payment->amount} DZD")
            ->line('You have 48 hours to download your report.')
            ->action('Download Report', url("/api/reports/{$this->payment->report?->id}/download"))
            ->line('Thank you for using VinCheck.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'payment_confirmed',
            'message'    => 'Your payment has been confirmed.',
            'payment_id' => $this->payment->id,
            'vehicle_id' => $this->payment->vehicle_id,
            'amount'     => $this->payment->amount,
            'expires_at' => $this->payment->expires_at,
            'report_id'  => $this->payment->report?->id,
        ];
    }
}
