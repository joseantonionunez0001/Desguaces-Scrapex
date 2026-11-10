<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Desguace') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f5f5f4; color: #1c1917; }
        .caja { text-align: center; }
        nav a { font-size: 1.25rem; color: #1c1917; text-decoration: none; padding: 0 .75rem; }
    </style>
</head>
<body>
    <div class="caja">
        <h1>{{ config('app.name', 'Desguace') }}</h1>
        <nav>
            @auth
                <a href="{{ route('dashboard') }}">Dashboard</a>
            @else
                <a href="{{ route('login') }}">Login</a> |
                <a href="{{ route('register') }}">Register</a>
            @endauth
        </nav>
    </div>
</body>
</html>
