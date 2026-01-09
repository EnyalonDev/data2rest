<!DOCTYPE html>
<html lang="{{ $lang }}" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Data2Rest')</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#38bdf8',
                        'p-text': 'var(--p-text)',
                        'p-muted': 'var(--p-muted)',
                        'p-title': 'var(--p-title)',
                        'glass-border': 'var(--p-border)',
                        'bg-glass': 'var(--p-card)',
                        dark: '#0b1120',
                    },
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        :root {
            --p-text: #1e293b;
            --p-title: #0f172a;
            --p-muted: #64748b;
            --p-bg: #f8fafc;
            --p-card: #ffffff;
            --p-border: rgba(15, 23, 42, 0.1);
            --p-input: #ffffff;
        }

        .dark {
            --p-text: #cbd5e1;
            --p-title: #ffffff;
            --p-muted: #94a3b8;
            --p-bg: #0b1120;
            --p-card: rgba(30, 41, 59, 0.4);
            --p-border: rgba(255, 255, 255, 0.1);
            --p-input: rgba(255, 255, 255, 0.05);
        }

        body {
            background-color: var(--p-bg);
            color: var(--p-text);
            font-family: 'Outfit', sans-serif;
        }

        .form-input {
            background-color: var(--p-input);
            color: var(--p-text);
            border: 2px solid var(--p-border);
            width: 100%;
            border-radius: 1rem;
            padding: 0.75rem 1rem 0.75rem 3rem;
            outline: none;
            transition: all 0.2s;
        }

        .form-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1);
        }

        .glass-card {
            background-color: var(--p-card);
            backdrop-filter: blur(40px);
            border: 1px solid var(--p-border);
            border-radius: 2.5rem;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
    </style>
    @include('partials.theme_engine')
</head>

<body class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Animated background elements -->
    <div
        class="absolute top-[-10%] right-[-10%] w-[500px] h-[500px] bg-primary/10 blur-[120px] rounded-full -z-10 animate-pulse">
    </div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[400px] h-[400px] bg-indigo-500/5 blur-[100px] rounded-full -z-10">
    </div>
    <div
        class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-[0.03] pointer-events-none -z-20">
    </div>

    @yield('content')
</body>

</html>