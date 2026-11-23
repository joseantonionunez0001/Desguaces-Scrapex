<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Desguace'))</title>
    <style>
        :root {
            --acento: #ea580c;
            --acento-oscuro: #c2410c;
            --oscuro: #1c1917;
            --texto-suave: #44403c;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Instrument Sans', system-ui, sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--oscuro);
            background:
                radial-gradient(circle at 90% 10%, #fed7aa 0, transparent 35%),
                radial-gradient(circle at 5% 90%, #fde68a 0, transparent 30%),
                #f5f5f4;
        }

        /* Cabecera */
        header {
            background: linear-gradient(90deg, var(--oscuro), #292524);
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid var(--acento);
            box-shadow: 0 4px 12px rgba(0, 0, 0, .18);
        }
        header strong {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: .02em;
        }
        header nav {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
        }
        header nav a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            padding: .45rem .95rem;
            border-radius: 999px;
            transition: background-color .2s ease;
        }
        header nav a:hover { background: rgba(255, 255, 255, .14); }
        header form.inline { margin: 0; }
        header form button {
            margin: 0;
            padding: .45rem .95rem;
            background: transparent;
            color: #fff;
            font-weight: 600;
            border: 1.5px solid #fff;
            border-radius: 999px;
            transition: background-color .2s ease, color .2s ease;
        }
        header form button:hover {
            background: #fff;
            color: var(--oscuro);
        }

        /* Contenido */
        main {
            width: calc(100% - 2rem);
            max-width: 720px;
            margin: auto;
            padding: 2.5rem 2rem;
            background: #fff;
            border-radius: 16px;
            border-top: 4px solid var(--acento);
            box-shadow: 0 12px 32px rgba(28, 25, 23, .1);
        }
        main h2 {
            margin: 0 0 .5rem;
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--oscuro);
        }
        main h3 {
            margin: 2rem 0 .5rem;
            font-size: 1.15rem;
            color: var(--acento-oscuro);
        }
        main p {
            color: var(--texto-suave);
            line-height: 1.6;
        }
        main ul {
            list-style: none;
            padding: 0;
            display: grid;
            gap: .6rem;
        }
        main li {
            background: #fafaf9;
            border: 1px solid #e7e5e4;
            border-left: 4px solid var(--acento);
            padding: .7rem 1rem;
            border-radius: 8px;
        }

        /* Formularios */
        label {
            display: block;
            margin-top: 1.1rem;
            font-weight: 600;
            font-size: .92rem;
            color: var(--texto-suave);
        }
        input:not([type="checkbox"]) {
            display: block;
            width: 100%;
            margin-top: .4rem;
            padding: .8rem 1rem;
            font-size: 1rem;
            font-family: inherit;
            border: 1.5px solid #d6d3d1;
            border-radius: 10px;
            background: #fafaf9;
            transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
        }
        input:not([type="checkbox"]):hover { border-color: #a8a29e; }
        input:not([type="checkbox"]):focus {
            outline: none;
            border-color: var(--acento);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(234, 88, 12, .2);
        }
        input[type="checkbox"] {
            width: 1.1rem;
            height: 1.1rem;
            margin: 0;
            accent-color: var(--acento);
            cursor: pointer;
        }
        label:has(input[type="checkbox"]) {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-top: 1.25rem;
            font-weight: 500;
            cursor: pointer;
            user-select: none;
        }
        .error {
            margin-top: .4rem;
            padding: .45rem .7rem;
            font-size: .87rem;
            color: #991b1b;
            background: #fef2f2;
            border-left: 3px solid #dc2626;
            border-radius: 6px;
        }

        /* Botones */
        main form button[type="submit"] {
            display: block;
            width: 100%;
            margin-top: 1.75rem;
            padding: .95rem 1rem;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, var(--acento), var(--acento-oscuro));
            border: 0;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 0 6px 16px rgba(234, 88, 12, .35);
            transition: transform .15s ease, box-shadow .2s ease;
        }
        main form button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(234, 88, 12, .4);
        }
        main form button[type="submit"]:active { transform: translateY(1px); }

        a:focus-visible,
        button:focus-visible {
            outline: 3px solid #fb923c;
            outline-offset: 3px;
        }

        /* Avisos y etiquetas */
        .aviso {
            background: #fff7ed;
            border: 1px solid #fdba74;
            border-left: 5px solid var(--acento);
            padding: 1rem 1.2rem;
            border-radius: 10px;
            color: #7c2d12;
            line-height: 1.6;
        }
        .etiqueta {
            display: inline-block;
            background: var(--oscuro);
            color: #fff;
            padding: .25rem .75rem;
            border-radius: 999px;
            margin: .2rem;
            font-size: .85rem;
            font-weight: 600;
        }
        form.inline { display: inline; }

        @media (max-width: 600px) {
            header {
                padding: 1rem;
                justify-content: center;
                text-align: center;
            }
            main {
                padding: 1.75rem 1.25rem;
            }
        }
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
