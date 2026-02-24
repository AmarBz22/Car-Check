<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class PartnerApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $partner;
    public $resetToken;
    public $resetUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $partner, string $resetToken)
    {
        $this->partner = $partner;
        $this->resetToken = $resetToken;

        // Generate password reset URL
        $this->resetUrl = url('/reset-password?token=' . $resetToken . '&email=' . urlencode($partner->email));
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome! Set Your Password - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.partner-approved',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
