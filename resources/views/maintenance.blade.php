<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ \App\Models\Setting::get('site_name', config('app.name')) }}</title>
        <style>
            body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f1f5f9; font-family: system-ui, sans-serif; color: #1e293b; }
            main { max-width: 32rem; margin: 1rem; padding: 2.5rem; text-align: center; background: #fff; border-radius: 1rem; box-shadow: 0 4px 24px rgba(0, 0, 0, .08); }
            h1 { margin: 0 0 1rem; font-size: 1.75rem; }
            p { margin: 0; line-height: 1.6; color: #475569; }
        </style>
    </head>
    <body>
        <main>
            <h1>{{ \App\Models\Setting::get('site_name', config('app.name')) }}</h1>
            <p>{{ $message }}</p>
        </main>
    </body>
</html>
