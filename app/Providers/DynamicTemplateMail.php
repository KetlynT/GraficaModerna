<?php

namespace App\Providers;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DynamicTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $htmlContent;
    public $mailSubject;

    public function __construct(string $subject, string $htmlContent)
    {
        $this->mailSubject = $subject;
        $this->htmlContent = $htmlContent;
    }

    public function build()
    {
        return $this->subject($this->mailSubject)
                    ->html($this->htmlContent);
    }
}