<?php
namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ReportGeneratedNotification extends Notification
{
    use Queueable;

    public function __construct(public Report $report) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isAdmin = $notifiable->role === 'admin';

        return (new MailMessage)
            ->subject('New Report Generated – VinCheck')
            ->greeting("Hello {$notifiable->name}!")
            ->line($isAdmin
                ? 'A new report has been submitted by a partner and is awaiting review.'
                : 'A report has been generated for your vehicle.'
            )
            ->line("Vehicle: {$this->report->vehicle->plate_number}")
            ->line("Report Type: {$this->report->report_type}")
            ->line("Risk Score: {$this->report->risk_score}")
            ->action(
                $isAdmin ? 'Review Report' : 'View Report',
                url($isAdmin
                    ? '/admin/reports/' . $this->report->id
                    : '/reports/' . $this->report->id)
            );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'report_generated',
            'message'     => "A new report has been generated for vehicle {$this->report->vehicle->plate_number}.",
            'report_id'   => $this->report->id,
            'vehicle_id'  => $this->report->vehicle_id,
            'report_type' => $this->report->report_type,
            'risk_score'  => $this->report->risk_score,
            'status'      => $this->report->status,
        ];
    }
}
