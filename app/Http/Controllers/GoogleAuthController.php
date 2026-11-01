<?php

namespace App\Http\Controllers;

use App\Services\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

/** "Continue with Google" on the sign-in and registration windows. */
class GoogleAuthController extends Controller
{
    public function redirect(Request $request, GoogleAuthService $google): SymfonyRedirect
    {
        if (! $google->isConfigured()) {
            return $google->failRedirect(__('Google sign-in is temporarily unavailable.'));
        }

        return $google->redirectToGoogle($request->query('redirect'));
    }

    public function callback(Request $request, GoogleAuthService $google): RedirectResponse
    {
        if (! $google->isConfigured()) {
            return $google->failRedirect(__('Google sign-in is temporarily unavailable.'));
        }

        $result = $google->handleCallback($request);

        if ($result['toast'] !== null) {
            session()->flash('mally_toast', $result['toast']);
        }

        return redirect($result['redirect']);
    }
}
