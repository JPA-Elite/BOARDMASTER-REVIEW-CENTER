<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Password Reset Success</title>
        <style>
            :root {
                color-scheme: light;
            }
            body {
                margin: 0;
                font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
                background: #f3f6fb;
                color: #1a2140;
            }
            .page {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
            }
            .card {
                width: 100%;
                max-width: 460px;
                background: #ffffff;
                border-radius: 18px;
                box-shadow: 0 18px 40px rgba(42, 55, 99, 0.15);
                border: 1px solid #e1e7f3;
                overflow: hidden;
            }
            .card__header {
                padding: 24px;
                background: linear-gradient(135deg, #2a3763 0%, #445892 100%);
                color: #ffffff;
            }
            .card__header h1 {
                margin: 0 0 6px;
                font-size: 26px;
                font-weight: 800;
            }
            .card__header p {
                margin: 0;
                font-size: 13px;
                color: #d9e3ff;
            }
            .card__body {
                padding: 24px;
            }
            .alert {
                margin-bottom: 14px;
                padding: 12px 14px;
                border-radius: 12px;
                font-size: 13px;
                background: #ecfdf3;
                color: #166534;
                border: 1px solid #bbf7d0;
            }
            .note {
                margin-top: 10px;
                font-size: 12px;
                color: #6c748f;
                text-align: center;
            }
            .email {
                font-weight: 700;
                color: #1f2a4a;
            }
        </style>
    </head>
    <body>
        <div class="page">
            <div class="card">
                <div class="card__header">
                    <h1>Password updated</h1>
                    <p>Board Masters Review Center</p>
                </div>
                <div class="card__body">
                    <div class="alert">
                        Your password has been reset successfully.
                    </div>
                    @if (! empty($email))
                        <div class="note">Account: <span class="email">{{ $email }}</span></div>
                    @endif
                    <div class="note">Return to the mobile app and log in with your new password.</div>
                </div>
            </div>
        </div>
    </body>
</html>
