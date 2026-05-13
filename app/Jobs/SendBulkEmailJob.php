<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBulkEmailJob implements ShouldQueue
{
    use Queueable;

    protected $chunk;
    protected $subject;
    protected $bodyTemplate;
    protected $ccEmails;
    protected $bccEmails;
    protected $isInvitation;
    protected $teamId;
    protected $roleId;

    /**
     * Create a new job instance.
     */
    public function __construct($chunk, $subject, $bodyTemplate, $ccEmails = [], $bccEmails = [], $isInvitation = false, $teamId = null, $roleId = null)
    {
        $this->chunk = $chunk;
        $this->subject = $subject;
        $this->bodyTemplate = $bodyTemplate;
        $this->ccEmails = $ccEmails;
        $this->bccEmails = $bccEmails;
        $this->isInvitation = $isInvitation;
        $this->teamId = $teamId;
        $this->roleId = $roleId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->chunk)) return;

        if (!$this->isInvitation) {
            // Bulk send (Newsletter style)
            // Use BCC for privacy.
            $allBcc = array_unique(array_merge($this->chunk, $this->bccEmails));
            $mail = \Illuminate\Support\Facades\Mail::bcc($allBcc);
            
            if (!empty($this->ccEmails)) {
                $mail->cc($this->ccEmails);
            }

            $mail->send(new \App\Mail\BulkEmail($this->subject, $this->bodyTemplate));
            return;
        }

        // Invitation mode: Individual processing
        $team = $this->teamId ? \App\Models\Team::find($this->teamId) : null;
        $teamName = $team ? $team->name : 'nuestro equipo';

        foreach ($this->chunk as $email) {
            $token = \Illuminate\Support\Str::random(32);
            
            \App\Models\TeamInvitation::updateOrCreate(
                ['email' => $email, 'team_id' => $this->teamId],
                ['role_id' => $this->roleId, 'token' => $token]
            );

            // Using standard registration route passing the token as query parameter.
            $inviteLink = route('register', ['invitation' => $token]);

            $customBody = str_replace(
                ['{email}', '{nombre_equipo}', '{enlace_invitacion}'],
                [$email, $teamName, $inviteLink],
                $this->bodyTemplate
            );

            $mail = \Illuminate\Support\Facades\Mail::to($email);
            if (!empty($this->ccEmails)) {
                $mail->cc($this->ccEmails);
            }
            if (!empty($this->bccEmails)) {
                $mail->bcc($this->bccEmails);
            }

            $mail->send(new \App\Mail\BulkEmail($this->subject, $customBody));
            
            // Short delay between individual emails to prevent rate limiting
            usleep(250000); // 0.25 seconds
        }
    }
}
