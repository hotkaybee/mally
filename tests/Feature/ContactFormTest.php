<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramMessage;
use App\Livewire\Storefront\ContactPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    private function submit(string $message = 'Hello *there* [link](x)')
    {
        return Livewire::test(ContactPage::class)
            ->set('firstName', 'Ali_')
            ->set('lastName', 'Valiev')
            ->set('phone', '+998 90 123 45 67')
            ->set('message', $message)
            ->call('submitForm');
    }

    public function test_message_is_queued_for_every_admin_chat(): void
    {
        Queue::fake();
        config(['services.telegram.admin_chat_ids' => '111, 222']);

        $this->submit()->assertSet('submitted', true);

        Queue::assertPushed(SendTelegramMessage::class, 2);
        Queue::assertPushed(SendTelegramMessage::class, fn ($job) => str_contains($job->text, 'Hello *there* [link](x)'));
    }

    public function test_telegram_job_sends_plain_text_over_verified_tls(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        config(['services.telegram.bot_token' => 'TEST_TOKEN']);

        (new SendTelegramMessage('111', 'Name: Ali_ *bold*'))->handle();

        Http::assertSent(fn ($request) => $request['text'] === 'Name: Ali_ *bold*'
            && ! isset($request['parse_mode'])
            && ($request->toPsrRequest()->getUri()->getScheme() === 'https'));
    }

    public function test_contact_form_is_rate_limited(): void
    {
        Queue::fake();
        config(['services.telegram.admin_chat_ids' => '111']);

        for ($i = 0; $i < 3; $i++) {
            $this->submit()->assertSet('submitted', true);
        }

        $this->submit()->assertSet('submitted', false)->assertDispatched('toast');
        Queue::assertPushed(SendTelegramMessage::class, 3);
    }
}
