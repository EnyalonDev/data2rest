<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-900">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Data2Rest</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        indigo: {
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                        },
                        slate: {
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>

<body class="h-full font-sans antialiased text-slate-200">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-indigo-500/10 blur-[100px]">
            </div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-purple-500/10 blur-[100px]">
            </div>
        </div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md mb-8 text-center">
            <div
                class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 shadow-lg shadow-indigo-500/30 mb-6">
                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold tracking-tight text-white">Bienvenido a Data2Rest</h2>
            <p class="mt-2 text-sm text-slate-400">
                Tu plataforma de gestión de datos y APIs lista para usar.
            </p>
        </div>

        <div class="sm:mx-auto sm:w-full sm:max-w-xl">
            <div class="glass py-8 px-4 shadow-2xl rounded-2xl sm:px-10 border border-slate-700/50">

                <!-- Step 1: Selection -->
                <div id="step-select" class="space-y-6">
                    <h3 class="text-lg font-medium text-white mb-4">Elige tu motor de base de datos</h3>

                    <!-- Option SQLite -->
                    <button onclick="selectType('sqlite')"
                        class="w-full group relative flex items-start gap-4 p-5 rounded-xl border border-slate-700 bg-slate-800/50 hover:bg-slate-800 hover:border-indigo-500/50 transition-all duration-300 text-left">
                        <div
                            class="flex-shrink-0 w-12 h-12 rounded-lg bg-blue-500/10 flex items-center justify-center group-hover:bg-blue-500/20 transition-colors">
                            <span class="text-xl">⚡</span>
                        </div>
                        <div>
                            <h4 class="text-base font-semibold text-white group-hover:text-blue-400 transition-colors">
                                Modo Rápido (SQLite)</h4>
                            <p class="mt-1 text-sm text-slate-400">Recomendado para empezar. Sin configuración, ideal
                                para desarrollo local, pruebas o proyectos pequeños. ¡Listo en 1 click!</p>
                        </div>
                        <div class="absolute right-5 top-5 opacity-0 group-hover:opacity-100 transition-opacity">
                            <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </button>

                    <!-- Option MySQL -->
                    <button onclick="selectType('mysql')"
                        class="w-full group relative flex items-start gap-4 p-5 rounded-xl border border-slate-700 bg-slate-800/50 hover:bg-slate-800 hover:border-orange-500/50 transition-all duration-300 text-left">
                        <div
                            class="flex-shrink-0 w-12 h-12 rounded-lg bg-orange-500/10 flex items-center justify-center group-hover:bg-orange-500/20 transition-colors">
                            <span class="text-xl">🐬</span>
                        </div>
                        <div>
                            <h4
                                class="text-base font-semibold text-white group-hover:text-orange-400 transition-colors">
                                MySQL / MariaDB</h4>
                            <p class="mt-1 text-sm text-slate-400">Para entornos de producción. Requiere un servidor
                                MySQL existente. Mayor rendimiento para múltiples usuarios.</p>
                        </div>
                    </button>

                    <!-- Option PostgreSQL -->
                    <button onclick="selectType('pgsql')"
                        class="w-full group relative flex items-start gap-4 p-5 rounded-xl border border-slate-700 bg-slate-800/50 hover:bg-slate-800 hover:border-indigo-500/50 transition-all duration-300 text-left">
                        <div
                            class="flex-shrink-0 w-12 h-12 rounded-lg bg-indigo-500/10 flex items-center justify-center group-hover:bg-indigo-500/20 transition-colors">
                            <span class="text-xl">🐘</span>
                        </div>
                        <div>
                            <h4
                                class="text-base font-semibold text-white group-hover:text-indigo-400 transition-colors">
                                PostgreSQL</h4>
                            <p class="mt-1 text-sm text-slate-400">La opción más robusta. Ideal para aplicaciones
                                complejas que requieren características avanzadas de SQL.</p>
                        </div>
                    </button>

                </div>

                <!-- Step 2: Form (Hidden by default) -->
                <form id="install-form" class="hidden space-y-6" onsubmit="submitInstall(event)">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-700">
                        <button type="button" onclick="goBack()"
                            class="p-2 -ml-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <h3 id="form-title" class="text-lg font-medium text-white">Configuración</h3>
                    </div>

                    <input type="hidden" name="type" id="input-type">

                    <!-- SQLite Confirmation -->
                    <div id="sqlite-fields" class="hidden">
                        <p class="text-sm text-slate-300 bg-slate-800/50 p-4 rounded-lg border border-slate-700">
                            Se creará una base de datos local en <code>/data/system.sqlite</code>.<br>
                            El usuario administrador por defecto será <strong>admin</strong>.
                        </p>
                    </div>

                    <!-- DB Connection Fields -->
                    <div id="db-fields" class="space-y-4 hidden">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Host</label>
                                <input type="text" name="host" value="localhost"
                                    class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Puerto</label>
                                <input type="text" name="port" id="input-port"
                                    class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Base de Datos</label>
                            <input type="text" name="database" value="data2rest_system"
                                class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                            <p class="mt-1 text-[10px] text-slate-500">Si no existe, intentaremos crearla.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Usuario</label>
                                <input type="text" name="username"
                                    class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Contraseña</label>
                                <input type="password" name="password"
                                    class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                            </div>
                        </div>
                    </div>

                    <div id="error-msg"
                        class="hidden text-sm text-red-400 bg-red-900/20 p-3 rounded-lg border border-red-900/50"></div>

                    <button type="submit" id="btn-submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 focus:ring-offset-slate-900 transition-all">
                        Instalar Data2Rest
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} Data2Rest. Open Source.
            </p>
        </div>
    </div>

    <script>
        function selectType(type) {
            document.getElementById('step-select').classList.add('hidden');
            document.getElementById('install-form').classList.remove('hidden');
            document.getElementById('input-type').value = type;

            const dbFields = document.getElementById('db-fields');
            const sqliteFields = document.getElementById('sqlite-fields');
            const portInput = document.getElementById('input-port');
            const title = document.getElementById('form-title');

            if (type === 'sqlite') {
                title.textContent = 'Configurar SQLite (Automático)';
                dbFields.classList.add('hidden');
                sqliteFields.classList.remove('hidden');
            } else {
                title.textContent = type === 'mysql' ? 'Configurar MySQL' : 'Configurar PostgreSQL';
                dbFields.classList.remove('hidden');
                sqliteFields.classList.add('hidden');
                portInput.value = type === 'mysql' ? '3306' : '5432';
            }
        }

        function goBack() {
            document.getElementById('install-form').classList.add('hidden');
            document.getElementById('step-select').classList.remove('hidden');
            document.getElementById('error-msg').classList.add('hidden');
        }

        async function submitInstall(e) {
            e.preventDefault();
            const form = e.target;
            const btn = document.getElementById('btn-submit');
            const errorDiv = document.getElementById('error-msg');

            // Loading state
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Instalando...';
            errorDiv.classList.add('hidden');

            try {
                const formData = new FormData(form);
                const response = await fetch('{{ $baseUrl }}install', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    throw new Error(result.message || 'Error desconocido');
                }
            } catch (err) {
                errorDiv.textContent = 'Error: ' + err.message;
                errorDiv.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = 'Reintentar Instalación';
            }
        }
    </script>
</body>

</html>