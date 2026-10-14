<?php

namespace App\Livewire\Storefront;

use App\Jobs\SendTelegramMessage;
use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Customer;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ContactPage extends Component
{
    use InteractsWithStore;

    public string $firstName = '';

    public string $lastName = '';

    public string $phone = '+998 ';

    /** Optional reply address; only used in the message text. */
    public string $email = '';

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

    protected function onCustomerAuthenticated(Customer $customer): void
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

        $email = mb_strtolower(trim($this->email));
        if ($email !== '' && (mb_strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            $this->dispatch('toast', message: __('Please enter a valid email address.'), type: 'error');

            return;
        }

        if (mb_strlen($firstName) > 100 || mb_strlen($lastName) > 100 || mb_strlen($msg) > 2000) {
            $this->dispatch('toast', message: __('Message is too long.'), type: 'error');

            return;
        }

        $rateKey = 'contact-form:'.request()->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            $this->dispatch('toast', message: __('Too many messages. Please try again later.'), type: 'error');

            return;
        }
        RateLimiter::hit($rateKey, 600);

        $this->phone = $normalizedPhone;

        $telegramText = "📩 Новое сообщение из контактной формы Mally\n\n"
            ."👤 Имя и фамилия: {$firstName} {$lastName}\n"
            ."📱 Телефон: {$normalizedPhone}\n"
            .($email !== '' ? "📧 Email: {$email}\n" : '')
            .'🕒 Дата: '.now()->timezone('Asia/Tashkent')->format('d.m.Y H:i')."\n\n"
            ."💬 Сообщение:\n{$msg}";

        $this->lastTelegramText = "Здравствуйте! Меня зовут {$firstName} {$lastName} ({$normalizedPhone}).\nСообщение: {$msg}";

        $chatIds = array_filter(array_map('trim', explode(',', (string) config('services.telegram.admin_chat_ids'))));
        foreach ($chatIds as $chatId) {
            SendTelegramMessage::dispatch($chatId, $telegramText);
        }

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
