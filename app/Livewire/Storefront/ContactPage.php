<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ContactPage extends Component
{
    use InteractsWithStore;

    public string $firstName = '';

    public string $lastName = '';

    public string $phone = '+998 ';

    public string $message = '';

    public bool $submitted = false;

    public ?string $lastTelegramText = null;

    public function mount(): void
    {
        $customer = $this->store()->getAuthenticatedCustomer();
        if ($customer) {
            $this->fillFromCustomer($customer);
        }
    }

    public function onCustomerAuthenticated(Customer $customer): void
    {
        $this->fillFromCustomer($customer);
    }

    protected function fillFromCustomer(Customer $customer): void
    {
        $this->firstName = (string) ($customer->first_name ?? '');
        $this->lastName = (string) ($customer->last_name ?? '');
        $phone = trim((string) ($customer->phone_number ?? ''));
        if ($phone !== '') {
            $this->phone = $this->store()->normalizeAndValidateUzPhone($phone) ?? $phone;
        }
    }

    public function submitForm(): void
    {
        $locale = $this->store()->getLocale();
        $customer = $this->store()->getAuthenticatedCustomer();
        if ($customer && trim((string) $customer->phone_number) !== '') {
            $this->phone = $this->store()->normalizeAndValidateUzPhone($customer->phone_number) ?? $customer->phone_number;
        }

        $firstName = trim($this->firstName);
        $lastName = trim($this->lastName);
        $normalizedPhone = $this->store()->normalizeAndValidateUzPhone($this->phone);
        $msg = trim($this->message);

        if ($firstName === '') {
            $err = match ($locale) {
                'uz' => 'Iltimos, ismingizni kiriting.',
                'en' => 'Please enter your first name.',
                default => 'Пожалуйста, укажите ваше имя.',
            };
            $this->dispatch('toast', message: $err, type: 'error');

            return;
        }

        if ($lastName === '') {
            $err = match ($locale) {
                'uz' => 'Iltimos, familiyangizni kiriting.',
                'en' => 'Please enter your last name.',
                default => 'Пожалуйста, укажите вашу фамилию.',
            };
            $this->dispatch('toast', message: $err, type: 'error');

            return;
        }

        if (! $normalizedPhone) {
            $err = match ($locale) {
                'uz' => "Iltimos, to'g'ri telefon raqamini kiriting (+998 va 9 ta raqam).",
                'en' => 'Please enter a valid phone number (+998 followed by 9 digits).',
                default => 'Пожалуйста, введите корректный номер телефона (+998 и 9 цифр).',
            };
            $this->dispatch('toast', message: $err, type: 'error');

            return;
        }

        if (mb_strlen($msg) < 2) {
            $err = match ($locale) {
                'uz' => 'Iltimos, xabar matnini kiriting.',
                'en' => 'Please enter your message.',
                default => 'Пожалуйста, введите текст вашего сообщения.',
            };
            $this->dispatch('toast', message: $err, type: 'error');

            return;
        }

        $this->phone = $normalizedPhone;

        $telegramText = "📩 *Новое сообщение из контактной формы Mally*\n\n"
            ."👤 *Имя и Фамилия:* {$firstName} {$lastName}\n"
            ."📱 *Телефон:* {$normalizedPhone}\n"
            .'🕒 *Дата:* '.now()->timezone('Asia/Tashkent')->format('d.m.Y H:i')."\n\n"
            ."💬 *Сообщение:*\n{$msg}";

        $this->lastTelegramText = "Здравствуйте! Меня зовут {$firstName} {$lastName} ({$normalizedPhone}).\nСообщение: {$msg}";

        $botToken = trim((string) config('services.telegram.bot_token'));
        $chatIdsRaw = (string) config('services.telegram.admin_chat_ids');
        $chatIds = array_values(array_filter(array_map('trim', explode(',', $chatIdsRaw))));

        if ($botToken !== '' && count($chatIds) > 0) {
            foreach ($chatIds as $chatId) {
                try {
                    Http::timeout(4)->withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                        'chat_id' => $chatId,
                        'text' => $telegramText,
                        'parse_mode' => 'Markdown',
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Telegram contact form send failed: '.$e->getMessage());
                }
            }
        }

        Log::info('Contact form submitted', [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $normalizedPhone,
            'message' => $msg,
        ]);

        $this->submitted = true;
        $this->message = '';
    }

    public function resetContactForm(): void
    {
        $this->submitted = false;
        $this->message = '';
    }

    public function render()
    {
        return view('livewire.storefront.contact-page', $this->sharedViewData());
    }
}
