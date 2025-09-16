<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subject;
    public $otp;

    public function __construct($subject, $otp)
    {
        $this->subject = $subject;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->view('email.otp-email')
            ->subject($this->subject)
            ->with([
                'otp' => $this->otp,
            ]);
    }
}

