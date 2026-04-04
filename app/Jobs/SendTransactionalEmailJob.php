<?php

namespace App\Jobs;

use App\Support\MailBrand;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTransactionalEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 120;

    public function __construct(
        public string $to,
        public string $subject,
        public string $view,
        public array $data = []
    ) {}

    public function handle(): void
    {
        $data = MailBrand::with($this->data);
        Mail::send($this->view, $data, function ($message) {
            $message->to($this->to)->subject($this->subject);
        });
    }
}
