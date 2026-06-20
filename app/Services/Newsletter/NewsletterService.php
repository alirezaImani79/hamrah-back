<?php

namespace App\Services\Newsletter;

use App\Jobs\SendNewsletterSms;
use App\Models\User;

class NewsletterService
{
    /**
     * Subscribe a user to the SMS newsletter. Idempotent: re-subscribing an
     * already-subscribed user leaves the original subscription timestamp intact.
     */
    public function subscribe(User $user): User
    {
        if (! $user->isSubscribedToNewsletter()) {
            $user->forceFill(['newsletter_subscribed_at' => now()])->save();
        }

        return $user;
    }

    /**
     * Unsubscribe a user from the SMS newsletter. Idempotent.
     */
    public function unsubscribe(User $user): User
    {
        if ($user->isSubscribedToNewsletter()) {
            $user->forceFill(['newsletter_subscribed_at' => null])->save();
        }

        return $user;
    }

    /**
     * Queue the given message for delivery to every subscribed user and return
     * the number of recipients the broadcast was dispatched to. Subscribers are
     * chunked so the broadcast never loads the full audience into memory.
     */
    public function broadcast(string $message): int
    {
        $recipients = 0;

        User::query()
            ->subscribedToNewsletter()
            ->whereNotNull('phone_number')
            ->select(['id', 'phone_number'])
            ->chunkById(500, function ($users) use ($message, &$recipients): void {
                foreach ($users as $user) {
                    SendNewsletterSms::dispatch($user->phone_number, $message);
                    $recipients++;
                }
            });

        return $recipients;
    }
}
