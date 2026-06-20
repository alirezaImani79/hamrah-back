<?php

namespace App\Console\Commands;

use App\Services\Newsletter\NewsletterService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('newsletter:send {message : The SMS text to broadcast to every subscriber}')]
#[Description('Broadcast an SMS newsletter to every subscribed user.')]
class SendNewsletter extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(NewsletterService $newsletter): int
    {
        $message = trim((string) $this->argument('message'));

        if ($message === '') {
            $this->error('The newsletter message cannot be empty.');

            return self::FAILURE;
        }

        $recipients = $newsletter->broadcast($message);

        if ($recipients === 0) {
            $this->warn('No subscribers found. Nothing was queued.');

            return self::SUCCESS;
        }

        $this->info("Queued the newsletter for {$recipients} subscriber(s).");

        return self::SUCCESS;
    }
}
