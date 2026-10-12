<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Код подтверждения Mally Store</title>
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
                                @if(($purpose ?? 'register') === 'reset')
                                    Восстановление пароля
                                @else
                                    Подтверждение электронной почты
                                @endif
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom: 24px; font-size: 14px; line-height: 22px; color: #57534E;">
                            Используйте этот проверочный код для продолжения в интернет-магазине Mally Store:
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="background-color: #FCECEC; border: 1px solid #F2D8D0; border-radius: 16px; width: 100%; max-width: 320px;">
                                <tr>
                                    <td align="center" style="padding: 20px 24px; font-family: 'Courier New', Courier, monospace; font-size: 34px; font-weight: bold; letter-spacing: 10px; color: #1C1917;">
                                        {{ $code }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom: 24px; font-size: 12px; line-height: 18px; color: #78716C;">
                            Код действителен в течение <strong>10 минут</strong>.<br>
                            Никому не сообщайте этот код.
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="border-top: 1px solid #F0E4E1; padding-top: 20px; font-size: 11px; line-height: 16px; color: #A8A29E;">
                            Если вы не запрашивали этот код, просто проигнорируйте это письмо.<br>
                            © {{ date('Y') }} Mally Store
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
