<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invite;
    public $inviteHeader;

    /**
     * Create a new message instance.
     */
    public function __construct($invite, $inviteHeader = 'You’re Invited to Join!')
    {
        $this->invite = $invite;
        $this->inviteHeader = $inviteHeader;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject( $this->inviteHeader )
            ->view('emails.user-invite')
            ->with([
                'name' => $this->invite->name,
                'inviteUrl' => config('app.frontend_url') . '/auth/register?token=' . $this->invite->token,
            ]);
    }
}
