<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subject;
    public $name;

    public function __construct($subject, $name)
    {
        $this->subject = $subject;
        $this->name = $name;
    }

    public function build()
    {
        return $this->view('email.welcome-email')
            ->subject($this->subject)
            ->with([
                'name' => $this->name,
            ]);
    }
}

