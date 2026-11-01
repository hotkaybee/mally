<?php

namespace App\Services;

use App\Mail\GoogleLinkedMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * "Continue with Google" for the storefront: OAuth 2.0 / OpenID Connect
 * Authorization Code flow through Socialite (state is checked there, the code
 * is exchanged on the server with the client secret, nothing is kept from
 * Google except the stable `sub`).
 *
 * Signing in with Google proves the person owns that Google account and
 * Google confirms the email, so no password is asked: the account is found by
 * `sub`; a confirmed email that already has a shopper account signs in to it
 * and links Google; otherwise a new account is created. A signed-in customer
 * can also use it from the profile to add a confirmed email (mode "link").
 */
class GoogleAuthService
{
    public const SESSION_REDIRECT = 'mally_google_redirect';

    /** The next callback adds Google to the signed-in account instead of signing in. */
    public const SESSION_MODE_LINK = 'mally_google_link_mode';

    /** What the next page shows in the auth modal: onboarding. */
    public const SESSION_OPEN = 'mally_google_open';

    public function __construct(
        private readonly CustomerAccountService $accounts,
        private readonly EmailAuthService $emailAuth,
        private readonly StorefrontService $storefront,
    ) {}

    public function isConfigured(): bool
    {
        return trim((string) config('services.google.client_id')) !== ''
            && trim((string) config('services.google.client_secret')) !== '';
    }

    /**
     * @param  bool  $link  add Google to the signed-in account (profile) instead of signing in
     * @param  string|null  $loginHint  the email typed in the sign-in form, pre-selects the Google account
     */
    public function redirectToGoogle(?string $redirectTo, bool $link = false, ?string $loginHint = null): SymfonyRedirect
    {
        session([self::SESSION_REDIRECT => $this->emailAuth->sanitizeRedirectPath($redirectTo)]);

        if ($link && $this->currentUser()) {
            session([self::SESSION_MODE_LINK => true]);
        } else {
            session()->forget(self::SESSION_MODE_LINK);
        }

        $params = ['prompt' => 'select_account'];
        $hint = $this->emailAuth->normalizeEmail((string) $loginHint);
        if ($this->emailAuth->isValidEmail($hint)) {
            $params['login_hint'] = $hint;
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->with($params)
            ->redirect();
    }

    /**
     * Finish the sign-in after Google sends the person back. Always returns
     * where to go next; the toast is the message for the customer.
     *
     * @return array{redirect: string, toast: array{message: string, type: string}|null}
     */
    public function handleCallback(Request $request): array
    {
        $linkMode = (bool) session()->pull(self::SESSION_MODE_LINK, false);
        $redirect = $this->emailAuth->sanitizeRedirectPath((string) session()->pull(self::SESSION_REDIRECT, '/'));

        // The person declined on Google's screen (access_denied) or the
        // callback was opened by hand.
        if ($request->filled('error') || ! $request->filled('code')) {
            return $this->result($redirect, __('Google sign-in was cancelled.'), 'info');
        }

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            // Includes InvalidStateException (forged or stale state).
            report($e);

            return $this->result($redirect, __('Could not sign in with Google. Please try again.'), 'error');
        }

        $raw = (array) ($google->user ?? []);
        $profile = [
            'sub' => (string) $google->getId(),
            'email' => (string) $google->getEmail(),
            'email_verified' => filter_var($raw['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'first_name' => (string) ($raw['given_name'] ?? ''),
            'last_name' => (string) ($raw['family_name'] ?? ''),
            // Set by Google for Workspace accounts only.
            'hosted_domain' => (string) ($raw['hd'] ?? ''),
        ];
        if ($profile['first_name'] === '' && $profile['last_name'] === '') {
            $profile['first_name'] = (string) $google->getName();
        }

        $current = $linkMode ? $this->currentUser() : null;
        if ($current) {
            return $this->attach($current, $profile, $redirect);
        }

        return $this->signIn($profile, $redirect);
    }

    public function failRedirect(string $message): RedirectResponse
    {
        session()->flash('mally_toast', ['message' => $message, 'type' => 'error']);

        return redirect()->route('home');
    }

    /** @param  array{sub: string, email: string, email_verified: bool, first_name: string, last_name: string, hosted_domain?: string}  $profile */
    private function signIn(array $profile, string $redirect): array
    {
        $res = $this->accounts->resolveGoogle($profile);

        switch ($res['result']) {
            case CustomerAccountService::OK:
                $this->accounts->login($res['user']);

                // The owner of an account that just got a new way in is told about it.
                if (! empty($res['linked'])) {
                    $this->notifyGoogleLinked($res['user']);
                }

                // Only a brand-new account is offered the optional Telegram step.
                if (! empty($res['created'])) {
                    session()->flash(self::SESSION_OPEN, 'onboarding');
                }

                return $this->result($redirect, ! empty($res['linked'])
                    ? __('Google is linked to your account. Next time just continue with Google.')
                    : __('You have signed in.'), 'success');

            case CustomerAccountService::GOOGLE_UNVERIFIED:
                return $this->result($redirect, __('Google has not confirmed this email address, so we cannot use it to sign in.'), 'error');

            case CustomerAccountService::GOOGLE_EMAIL_TAKEN:
                return $this->result($redirect, __('An account with this email already exists. Please sign in the way you registered.'), 'error');

            default:
                return $this->result($redirect, __('Could not sign in with Google. Please try again.'), 'error');
        }
    }

    /** @param  array{sub: string, email: string, email_verified: bool, first_name: string, last_name: string, hosted_domain?: string}  $profile */
    private function attach(User $user, array $profile, string $redirect): array
    {
        return match ($this->accounts->attachGoogle($user, $profile)) {
            CustomerAccountService::OK => $this->result($redirect, __('Google is connected, your email is confirmed.'), 'success'),
            CustomerAccountService::GOOGLE_UNVERIFIED => $this->result($redirect, __('Google has not confirmed this email address, so we cannot use it to sign in.'), 'error'),
            CustomerAccountService::GOOGLE_EMAIL_TAKEN => $this->result($redirect, __('This email already belongs to another profile.'), 'error'),
            default => $this->result($redirect, __('This Google account is already linked to a profile.'), 'error'),
        };
    }

    private function notifyGoogleLinked(User $user): void
    {
        try {
            Mail::to($user->email)->send(new GoogleLinkedMail);
        } catch (Throwable $e) {
            // A mail problem must not block the sign-in.
            report($e);
        }
    }

    /** The signed-in shopper, read fresh from the database. */
    private function currentUser(): ?User
    {
        $userId = $this->storefront->getCurrentCustomer()?->user_id;

        return $userId ? User::shoppers()->find($userId) : null;
    }

    /** @return array{redirect: string, toast: array{message: string, type: string}} */
    private function result(string $redirect, string $message, string $type): array
    {
        return ['redirect' => $redirect, 'toast' => ['message' => $message, 'type' => $type]];
    }
}
