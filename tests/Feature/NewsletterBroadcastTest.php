<?php

use App\Contracts\SmsSender;
use App\Jobs\SendNewsletterSms;
use App\Models\User;
use App\Services\Sms\FakeSmsSender;
use Illuminate\Support\Facades\Queue;

it('queues a newsletter job only for subscribed users', function () {
    Queue::fake();

    $subscribed = User::factory()->subscribedToNewsletter()->count(2)->create();
    User::factory()->create(); // not subscribed

    $this->artisan('newsletter:send', ['message' => 'Big news today!'])
        ->expectsOutputToContain('2 subscriber(s)')
        ->assertSuccessful();

    Queue::assertPushed(SendNewsletterSms::class, 2);

    foreach ($subscribed as $user) {
        Queue::assertPushed(
            SendNewsletterSms::class,
            fn (SendNewsletterSms $job): bool => $job->phoneNumber === $user->phone_number
                && $job->message === 'Big news today!',
        );
    }
});

it('warns when there are no subscribers', function () {
    Queue::fake();

    $this->artisan('newsletter:send', ['message' => 'Hello'])
        ->expectsOutputToContain('No subscribers')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('rejects an empty newsletter message', function () {
    Queue::fake();

    $this->artisan('newsletter:send', ['message' => '   '])
        ->assertFailed();

    Queue::assertNothingPushed();
});

it('delivers the message through the sms sender when the job runs', function () {
    $sms = new FakeSmsSender;
    $this->app->instance(SmsSender::class, $sms);

    (new SendNewsletterSms('+15551230000', 'Big news today!'))->handle($sms);

    expect($sms->lastMessageTo('+15551230000'))->toBe('Big news today!');
});
