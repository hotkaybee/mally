<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('You already have a Mally account') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #FAF7F5; font-family: Arial, Helvetica, sans-serif; color: #1C1917; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FAF7F5; width: 100%; margin: 0; padding: 36px 16px;">
        <tr>
            <td align="center" valign="top">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 520px; width: 100%; background-color: #FFFFFF; border: 1px solid #EAE3DE; border-radius: 24px; padding: 40px 32px;">
                    <tr>
                        <td align="center" style="padding-bottom: 8px;">
                            <span style="font-family: Georgia, 'Times New Roman', serif; font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #1C1917; text-transform: uppercase;">
                                MALLY
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom: 28px;">
                            <span style="font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: #A8A29E; font-weight: bold;">
                                Mally Store • 100% Authentic Beauty
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom: 12px;">
                            <h1 style="margin: 0; font-family: Georgia, 'Times New Roman', serif; font-size: 22px; font-weight: bold; color: #1C1917;">
                                {{ __('You already have a Mally account') }}
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom: 24px; font-size: 14px; line-height: 22px; color: #57534E;">
                            {{ __('Someone tried to sign up on the Mally Store website with this email, but it already has an account. Sign in, or reset your password if you forgot it.') }}
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <a href="{{ url('/') }}" style="display: inline-block; padding: 14px 28px; border-radius: 12px; background-color: #1C1917; color: #FAF7F5; font-size: 12px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; text-decoration: none;">
                                {{ __('Go to Mally Store') }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="border-top: 1px solid #F0E4E1; padding-top: 20px; font-size: 11px; line-height: 16px; color: #A8A29E;">
                            {{ __('If it wasn’t you, just ignore this email.') }}<br>
                            © {{ date('Y') }} Mally Store
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
