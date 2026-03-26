<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Your Email</title>
</head>
<body style="margin:0;padding:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#1a2140;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:20px;overflow:hidden;border:1px solid #dde4f2;">
                <tr>
                    <td style="padding:28px 28px 20px;background:linear-gradient(135deg,#2a3763 0%,#445892 100%);">
                        <div style="font-size:12px;letter-spacing:1.2px;text-transform:uppercase;color:#cfd8ef;font-weight:700;">{{ $appName }}</div>
                        <h1 style="margin:10px 0 6px;font-size:30px;line-height:1.15;color:#ffffff;">Verify your email</h1>
                        <p style="margin:0;color:#d9e3ff;font-size:14px;line-height:1.5;">
                            Protect your account and unlock full access to your review dashboard.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 14px;font-size:16px;font-weight:700;color:#2a3763;">Hi {{ $name }},</p>
                        <p style="margin:0 0 18px;font-size:15px;line-height:1.6;color:#334269;">
                            We received a request to activate <strong>{{ $email }}</strong>. Click the button below to verify your email and continue using Board Masters.
                        </p>
                        <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 18px;">
                            <tr>
                                <td align="center" bgcolor="#c53d57" style="border-radius:12px;">
                                    <a href="{{ $actionUrl }}" style="display:inline-block;padding:14px 24px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">
                                        Verify Email Address
                                    </a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#6c748f;">
                            This secure link expires in {{ $expireMinutes }} minutes. If you did not create this account, you can safely ignore this email.
                        </p>
                        <p style="margin:16px 0 0;font-size:12px;line-height:1.6;color:#8c95b0;">
                            If the button does not work, copy and paste this link into your browser:
                            <br>
                            <a href="{{ $actionUrl }}" style="color:#2a3763;word-break:break-all;">{{ $actionUrl }}</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
