<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#1a2140;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="min-height:100vh;padding:18px;">
    <tr>
        <td align="center" valign="middle">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #dde4f2;">
                <tr>
                    <td style="padding:26px;background:linear-gradient(135deg,#2a3763 0%,#445892 100%);">
                        <h1 style="margin:0;font-size:30px;line-height:1.2;color:#ffffff;">{{ $title }}</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 24px 26px;">
                        <p style="margin:0 0 16px;font-size:16px;line-height:1.65;color:#334269;">{{ $message }}</p>
                        <div style="display:inline-block;padding:8px 12px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;{{ $status === 'success' ? 'background:#e6f8ef;color:#12834f;' : 'background:#fdecef;color:#a8223a;' }}">
                            {{ $status === 'success' ? 'Status: Verified' : 'Status: Failed' }}
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
