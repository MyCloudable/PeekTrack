<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email sent to a superintendent's manager when a kicked-back card has
 * aged past the escalation threshold without being fixed.
 *
 * Routed through Laravel's mailer — PeekTrack production uses SMTP2GO
 * (confirmed by dev 2026-05-08), so deployment requires no mail config
 * changes.
 *
 * USAGE
 *   Mail::to($manager->email)->send(new KickbackEscalation([
 *       'manager_name'    => '...',
 *       'super_name'      => '...',
 *       'super_email'     => '...',
 *       'job_number'      => 'J24-001',
 *       'card_link'       => 'uuid...',
 *       'kicked_back_at'  => '2026-05-04 10:00:00',
 *       'kickback_count'  => 1,
 *       'days_overdue'    => 3,
 *   ]));
 */
class KickbackEscalation extends Mailable
{
    use Queueable, SerializesModels;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function build()
    {
        return $this->subject("[PeekTrack] Overdue kickback — {$this->payload['job_number']}")
            ->view('emails.ai.kickback-escalation')
            ->with($this->payload);
    }
}
