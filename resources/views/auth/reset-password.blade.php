<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Reset Password</title>
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
            label {
                display: block;
                font-size: 13px;
                font-weight: 600;
                color: #2a3763;
                margin-bottom: 6px;
            }
            input {
                width: 100%;
                border: 1px solid #d7dfef;
                border-radius: 12px;
                padding: 12px 14px;
                font-size: 14px;
                outline: none;
                box-sizing: border-box;
            }
            input:focus {
                border-color: #2a3763;
                box-shadow: 0 0 0 2px rgba(42, 55, 99, 0.15);
            }
            .field {
                margin-bottom: 14px;
            }
            .error {
                margin-top: 6px;
                font-size: 12px;
                color: #c53d57;
            }
            .alert {
                margin-bottom: 14px;
                padding: 10px 12px;
                border-radius: 10px;
                font-size: 12px;
            }
            .alert--success {
                background: #ecfdf3;
                color: #166534;
            }
            .alert--error {
                background: #fff1f2;
                color: #9f1239;
            }
            .actions {
                margin-top: 18px;
            }
            .btn {
                width: 100%;
                border: none;
                border-radius: 14px;
                padding: 12px;
                background: #2a3763;
                color: #ffffff;
                font-weight: 700;
                font-size: 14px;
                cursor: pointer;
            }
            .btn:disabled {
                cursor: not-allowed;
                opacity: 0.65;
            }
            .note {
                margin-top: 14px;
                font-size: 12px;
                color: #6c748f;
                text-align: center;
            }
            .toggle-row {
                margin-top: 6px;
            }
            .toggle {
                display: inline-flex;
                gap: 8px;
                align-items: center;
                font-size: 12px;
                font-weight: 600;
                color: #44507a;
            }
            .toggle input {
                width: auto;
            }
            .strength {
                margin-top: 10px;
                margin-bottom: 14px;
            }
            .strength__row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 12px;
                color: #6c748f;
                font-weight: 600;
                margin-bottom: 6px;
            }
            .strength__label {
                font-weight: 700;
            }
            .strength__bar {
                position: relative;
                height: 6px;
                border-radius: 999px;
                background: rgba(42, 55, 99, 0.1);
                overflow: hidden;
            }
            .strength__bar span {
                position: absolute;
                inset: 0;
                width: 5%;
                background: #f59e0b;
                border-radius: 999px;
                transition: width 0.2s ease, background 0.2s ease;
            }
            .strength__hint {
                margin-top: 6px;
                font-size: 11px;
                color: #6c748f;
                font-weight: 600;
            }
            .hint {
                margin-top: 6px;
                font-size: 12px;
                font-weight: 600;
            }
            .hint--ok {
                color: #166534;
            }
            .hint--warn {
                color: #c53d57;
            }
        </style>
    </head>
    <body>
        <div class="page">
            <div class="card">
                <div class="card__header">
                    <h1>Reset your password</h1>
                    <p>BoardMasters Review Center</p>
                </div>
                <div class="card__body">
                    @if (session('status'))
                        <div class="alert alert--success">{{ session('status') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert--error">{{ session('error') }}</div>
                    @endif

                    @if ($expired)
                        <div class="alert alert--error">
                            This reset password link has expired or was already used.
                        </div>
                    @else
                        <form method="POST" action="{{ route('password.store') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">

                            <input id="email" name="email" type="hidden" value="{{ old('email', $email) }}" required autocomplete="username">
                            @error('email')
                                <div class="error">{{ $message }}</div>
                            @enderror

                            <div class="field">
                                <label for="password">Password</label>
                                <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="Enter your password">
                                @error('password')
                                    <div class="error">{{ $message }}</div>
                                @enderror
                                <div class="toggle-row">
                                    <label class="toggle">
                                        <input type="checkbox" id="showPassword">
                                        Show password
                                    </label>
                                </div>
                                <div class="strength">
                                    <div class="strength__row">
                                        <span>Password strength</span>
                                        <span id="strengthLabel" class="strength__label">Too weak</span>
                                    </div>
                                    <div class="strength__bar">
                                        <span id="strengthBar"></span>
                                    </div>
                                    <div class="strength__hint">
                                        Use 8+ chars with upper, lower, number, and symbol.
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <label for="password_confirmation">Confirm Password</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Confirm your password">
                                <div id="matchHint" class="hint"></div>
                            </div>

                            <div class="actions">
                                <button class="btn" type="submit">Reset Password</button>
                            </div>
                        </form>
                        <div class="note">After resetting, you will see a success page and return to the mobile app.</div>
                    @endif
                </div>
            </div>
        </div>
        <script>
            (function () {
                const passwordInput = document.getElementById('password');
                const confirmInput = document.getElementById('password_confirmation');
                const strengthLabel = document.getElementById('strengthLabel');
                const strengthBar = document.getElementById('strengthBar');
                const matchHint = document.getElementById('matchHint');
                const showPassword = document.getElementById('showPassword');

                if (!passwordInput || !confirmInput || !strengthLabel || !strengthBar || !matchHint) {
                    return;
                }

                function scorePassword(value) {
                    let score = 0;
                    if (value.length >= 8) score++;
                    if (value.length >= 12) score++;
                    if (/[A-Z]/.test(value)) score++;
                    if (/[a-z]/.test(value)) score++;
                    if (/\d/.test(value)) score++;
                    if (/[^A-Za-z0-9]/.test(value)) score++;
                    return Math.min(score / 6, 1);
                }

                function updateStrength() {
                    const value = passwordInput.value || '';
                    const normalized = scorePassword(value);
                    let label = 'Too weak';
                    let color = '#f59e0b';

                    if (normalized >= 0.85) {
                        label = 'Strong';
                        color = '#16a34a';
                    } else if (normalized >= 0.6) {
                        label = 'Good';
                        color = '#2563eb';
                    } else if (normalized >= 0.35) {
                        label = 'Fair';
                        color = '#f59e0b';
                    } else {
                        label = 'Too weak';
                        color = '#c53d57';
                    }

                    strengthLabel.textContent = label;
                    strengthLabel.style.color = color;
                    strengthBar.style.background = color;
                    strengthBar.style.width = (Math.max(normalized, 0.05) * 100).toFixed(0) + '%';
                }

                function updateMatch() {
                    const passwordValue = passwordInput.value || '';
                    const confirmValue = confirmInput.value || '';
                    if (!confirmValue) {
                        matchHint.textContent = '';
                        matchHint.className = 'hint';
                        return;
                    }
                    if (passwordValue === confirmValue) {
                        matchHint.textContent = 'Passwords match.';
                        matchHint.className = 'hint hint--ok';
                        return;
                    }
                    matchHint.textContent = 'Passwords do not match.';
                    matchHint.className = 'hint hint--warn';
                }

                passwordInput.addEventListener('input', function () {
                    updateStrength();
                    updateMatch();
                });
                confirmInput.addEventListener('input', updateMatch);

                if (showPassword) {
                    showPassword.addEventListener('change', function () {
                        passwordInput.type = showPassword.checked ? 'text' : 'password';
                    });
                }
                updateStrength();
                updateMatch();
            })();
        </script>
    </body>
</html>
