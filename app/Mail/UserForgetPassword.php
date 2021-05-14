<?php

namespace App\Mail;

use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class UserForgetPassword extends Mailable
{
    use Queueable;
    use SerializesModels;


    public User $user ;
    public string $token_password_reset ;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user ;
        $this->token_password_reset = PasswordReset::getToken($user->email);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.verification_password')->with([
            'user' => $this->user,
            'token' => $this->token_password_reset,
        ]);
    }

    public static function mailSend(User $user)
    {
        Mail::to($user)->send(new self($user));
    }
}
