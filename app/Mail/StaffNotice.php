<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** One small mailable for plain system notices. All interpolated values are escaped. */
class StaffNotice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(public string $subjectLine, public array $lines, public ?string $actionUrl = null) {}

    public function envelope(): Envelope { return new Envelope(subject: $this->subjectLine); }

    public function content(): Content
    {
        $html = '<div style="font-family:Arial,sans-serif;color:#202938">'
            .collect($this->lines)->map(fn ($l) => '<p>'.e($l).'</p>')->join('')
            .($this->actionUrl ? '<p><a href="'.e($this->actionUrl).'">'.e($this->actionUrl).'</a></p>' : '')
            .'</div>';

        return new Content(htmlString: $html);
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('Mail delivery failed', ['subject' => $this->subjectLine, 'error' => $e->getMessage()]);
    }
}
