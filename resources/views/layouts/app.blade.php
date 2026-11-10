<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Desguace'))</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f5f5f4; color: #1c1917; }
        header { background: #1c1917; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #fff; text-decoration: none; margin-left: 1rem; }
        main { max-width: 640px; margin: 2rem auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        label { display: block; margin-top: 1rem; font-weight: 600; }
        input { width: 100%; padding: .6rem; margin-top: .3rem; border: 1px solid #d6d3d1; border-radius: 6px; box-sizing: border-box; }
        button { margin-top: 1.5rem; padding: .6rem 1.2rem; background: #1c1917; color: #fff; border: 0; border-radius: 6px; cursor: pointer; }
        .error { color: #b91c1c; font-size: .9rem; margin-top: .3rem; }
        .aviso { background: #fef3c7; border: 1px solid #f59e0b; padding: 1rem; border-radius: 6px; }
        .etiqueta { display: inline-block; background: #e7e5e4; padding: .2rem .6rem; border-radius: 999px; margin: .2rem; }
        form.inline { display: inline; }
        header form button { margin: 0; background: transparent; border: 1px solid #fff; padding: .4rem .8rem; }
    </style>
</head>
<body>
    <header>
        <strong>{{ config('app.name', 'Desguace') }}</strong>
        <nav>
            @auth
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit">Cerrar sesión</button>
                </form>
            @else
                <a href="{{ route('login') }}">Login</a>
                <a href="{{ route('register') }}">Register</a>
            @endauth
        </nav>
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>
