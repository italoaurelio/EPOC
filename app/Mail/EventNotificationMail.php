<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->payload['subject'] ?? 'Escalada para o Céu');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.event-notification', with: ['payload' => $this->payload]);
    }
}
