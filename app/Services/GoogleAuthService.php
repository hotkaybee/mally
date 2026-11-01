<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * "Continue with Google" for the storefront: OAuth 2.0 / OpenID Connect
 * Authorization Code flow through Socialite (state is checked there, the code
 * is exchanged on the server with the client secret, nothing is kept from
 * Google except the stable `sub`).
 *
 * The account is found by `sub`. A verified Google email may create a new
 * account; if the email already belongs to an account, that account is never
 * merged automatically: the owner proves it with the account password first
 * (the `mally_google_link` session entry, see InteractsWithStore).
 */
class GoogleAuthService
{
    public const SESSION_REDIRECT = 'mally_google_redirect';

    public const SESSION_LINK = 'mally_google_link';

    /** What the next page shows in the auth modal: onboarding | link. */
    public const SESSION_OPEN = 'mally_google_open';

    public const LINK_TTL_MINUTES = 15;

    public function __construct(
        private readonly CustomerAccountService $accounts,
        private readonly EmailAuthService $emailAuth,
    ) {}

    public function isConfigured(): bool
    {
        return trim((string) config('services.google.client_id')) !== ''
            && trim((string) config('services.google.client_secret')) !== '';
    }

    public function redirectToGoogle(?string $redirectTo): SymfonyRedirect
    {
        session([self::SESSION_REDIRECT => $this->emailAuth->sanitizeRedirectPath($redirectTo)]);

        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->with(['prompt' => 'select_account'])
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
        $redirect = $this->emailAuth->sanitizeRedirectPath((string) session()->pull(self::SESSION_REDIRECT, '/'));

        // The person declined on Google's screen (access_denied) or the
        // callback was opened by hand.
        if ($request->filled('error') || ! $request->filled('code')) {
            return $this->fail($redirect, __('Google sign-in was cancelled.'), 'info');
        }

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            // Includes InvalidStateException (forged or stale state).
            report($e);

            return $this->fail($redirect, __('Could not sign in with Google. Please try again.'));
        }

        $raw = (array) ($google->user ?? []);
        $profile = [
            'sub' => (string) $google->getId(),
            'email' => (string) $google->getEmail(),
            'email_verified' => filter_var($raw['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'first_name' => (string) ($raw['given_name'] ?? ''),
            'last_name' => (string) ($raw['family_name'] ?? ''),
        ];
        if ($profile['first_name'] === '' && $profile['last_name'] === '') {
            $profile['first_name'] = (string) $google->getName();
        }

        $res = $this->accounts->resolveGoogle($profile);

        switch ($res['result']) {
            case CustomerAccountService::OK:
                /** @var User $user */
                $user = $res['user'];
                $this->accounts->login($user);

                if (! empty($res['created'])) {
                    session()->flash(self::SESSION_OPEN, 'onboarding');
                }

                return [
                    'redirect' => $redirect,
                    'toast' => ['message' => __('You have signed in.'), 'type' => 'success'],
                ];

            case CustomerAccountService::GOOGLE_UNVERIFIED:
                return $this->fail($redirect, __('Google has not confirmed this email address, so we cannot use it to sign in.'));

            case CustomerAccountService::GOOGLE_EMAIL_TAKEN:
                $owner = $res['user'] ?? null;
                if (! $owner || $owner->is_admin || filled($owner->google_id) || ! $owner->hasPassword()) {
                    return $this->fail($redirect, __('An account with this email already exists. Please sign in the way you registered.'));
                }

                // Linking needs the account password, asked in the auth modal.
                session([self::SESSION_LINK => [
                    'user_id' => $owner->id,
                    'sub' => $profile['sub'],
                    'email' => mb_strtolower($profile['email']),
                    'until' => now()->addMinutes(self::LINK_TTL_MINUTES)->timestamp,
                ]]);
                session()->flash(self::SESSION_OPEN, 'link');

                return ['redirect' => $redirect, 'toast' => null];

            default:
                return $this->fail($redirect, __('Could not sign in with Google. Please try again.'));
        }
    }

    public function failRedirect(string $message): RedirectResponse
    {
        session()->flash('mally_toast', ['message' => $message, 'type' => 'error']);

        return redirect()->route('home');
    }

    /** @return array{redirect: string, toast: array{message: string, type: string}} */
    private function fail(string $redirect, string $message, string $type = 'error'): array
    {
        return ['redirect' => $redirect, 'toast' => ['message' => $message, 'type' => $type]];
    }
}
