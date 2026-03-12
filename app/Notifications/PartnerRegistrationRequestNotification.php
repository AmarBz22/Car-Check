<?php
namespace App\Notifications;

use App\Models\RegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PartnerRegistrationRequestNotification extends Notification
{
    use Queueable;

    public function __construct(public RegistrationRequest $registrationRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Partner Registration Request – VinCheck')
            ->greeting("Hello {$notifiable->name}!")
            ->line('A new partner registration request has been submitted.')
            ->line("Name: {$this->registrationRequest->name}")
            ->line("Company: {$this->registrationRequest->company_name}")
            ->line("Email: {$this->registrationRequest->email}")
            ->action('Review Request', url('/admin/registration-requests'))
            ->line('Please review and approve or reject this request.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'partner_registration_request',
            'message'        => "New partner request from {$this->registrationRequest->company_name}.",
            'request_id'     => $this->registrationRequest->id,
            'applicant_name' => $this->registrationRequest->name,
            'company_name'   => $this->registrationRequest->company_name,
            'email'          => $this->registrationRequest->email,
        ];
    }
}
