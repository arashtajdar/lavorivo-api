<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewEmployeeRegistration extends Mailable
{
    use Queueable, SerializesModels;

    public $password;
    public $user;
    public $verificationUrl;

    public function __construct($user, $password, $verificationUrl)
    {
        $this->user = $user;
        $this->password = $password;
        $this->verificationUrl = $verificationUrl;
    }

    public function build()
    {
        return $this->view('emails.new-employee-registration')
            ->subject('Welcome to ' . config('app.name') . '! Verify Your Email');
    }
}
