<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Desguace') }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Instrument Sans', system-ui, sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #fff;
            background:
                radial-gradient(circle at 85% 15%, rgba(234, 88, 12, .45) 0, transparent 40%),
                radial-gradient(circle at 10% 90%, rgba(251, 191, 36, .25) 0, transparent 35%),
                linear-gradient(160deg, #1c1917 0%, #292524 60%, #431407 100%);
        }

        .caja {
            text-align: center;
            max-width: 560px;
            width: 100%;
            padding: 3rem 2rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .12);
            box-shadow: 0 20px 50px rgba(0, 0, 0, .35);
            backdrop-filter: blur(6px);
        }

        .caja h1 {
            margin: 0 0 .5rem;
            font-size: clamp(2rem, 6vw, 2.8rem);
            font-weight: 800;
            letter-spacing: .02em;
        }

        .caja::before {
            content: "";
            display: block;
            width: 64px;
            height: 4px;
            margin: 0 auto 1.25rem;
            border-radius: 999px;
            background: #ea580c;
        }

        nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: .75rem;
            margin-top: 2rem;
        }

        nav a {
            color: #fff;
            text-decoration: none;
            font-size: 1.05rem;
            font-weight: 700;
            padding: .75rem 1.6rem;
            border-radius: 999px;
            background: linear-gradient(135deg, #ea580c, #c2410c);
            box-shadow: 0 6px 16px rgba(234, 88, 12, .4);
            transition: transform .15s ease, box-shadow .2s ease;
        }

        nav a:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(234, 88, 12, .5);
        }

        nav a:focus-visible {
            outline: 3px solid #fdba74;
            outline-offset: 3px;
        }

        /* Separador "|" del texto de la vista: se muestra como texto discreto */
        nav {
            color: rgba(255, 255, 255, .5);
        }
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
