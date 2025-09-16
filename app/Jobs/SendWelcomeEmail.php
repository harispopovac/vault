<?php

namespace App\Jobs;

use App\Mail\WelcomeEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $subject;
    public $email;
    public $name;

    public function __construct($subject, $email, $name)
    {
        $this->subject = $subject;
        $this->email = $email;
        $this->name = $name;
    }
    public function handle(): void
    {
        $email = new WelcomeEmail(
            $this->subject,
            $this->name
        );

        Mail::to($this->email)->send($email);
    }
}
