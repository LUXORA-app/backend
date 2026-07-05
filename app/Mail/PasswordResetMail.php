<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset Code - Luxora',
        );
    }

    public function build()
    {
        return $this->html(
            <<<HTML
                <html>
                    <body>
                        <h1>Password Reset Request</h1>
                        <p>Your password reset code is:</p>
                        <h2 style="background-color: #f0f0f0; padding: 10px; display: inline-block;">{$this->code}</h2>
                        <p>This code will expire in 60 minutes.</p>
                        <p>If you didn't request this, you can ignore this email.</p>
                    </body>
                </html>
            HTML
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
