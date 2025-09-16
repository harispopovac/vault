<?php

namespace App\Jobs;

use App\Mail\OtpEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class SendOtpEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $subject;
    public $mfa_email;
    public $otp;

    public function __construct($subject, $mfa_email, $otp)
    {
        $this->subject = $subject;
        $this->mfa_email = $mfa_email;
        $this->otp = $otp;
    }
    public function handle(): void
    {
        $email = new OtpEmail(
            $this->subject,
            $this->otp
        );

        Mail::to($this->mfa_email)->send($email);
    }
}
