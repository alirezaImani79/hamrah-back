<?php

namespace App\Jobs;

use App\Contracts\SmsSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTripUpdatedSms implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $phoneNumber,
        public string $message,
    ) {}

    /**
     * Notify a single passenger that their trip changed.
     */
    public function handle(SmsSender $smsSender): void
    {
        $smsSender->send($this->phoneNumber, $this->message);
    }
}
