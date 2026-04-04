<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Not Found - {{ config('app.name', 'BoardMasters Review Center') }}</title>
        <style>
            :root { color-scheme: light; }
            body {
                margin: 0;
                font-family: "Figtree", system-ui, -apple-system, "Segoe UI", sans-serif;
                background: #f8fafc;
                color: #0f172a;
                display: grid;
                place-items: center;
                min-height: 100vh;
                padding: 24px;
            }
            .card {
                max-width: 520px;
                width: 100%;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 28px;
                box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
            }
            h1 {
                font-size: 28px;
                margin: 0 0 8px 0;
            }
            p {
                margin: 0 0 18px 0;
                color: #475569;
                line-height: 1.5;
            }
            a {
                display: inline-block;
                text-decoration: none;
                background: #0f172a;
                color: #ffffff;
                padding: 10px 16px;
                border-radius: 10px;
                font-weight: 600;
            }
            .code {
                margin-top: 16px;
                font-size: 12px;
                color: #94a3b8;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Not Found</h1>
            <p>The page you are looking for does not exist or has moved.</p>
            <a href="/">Back to Home</a>
            <div class="code">Error code: 404</div>
        </div>
    </body>
</html>
