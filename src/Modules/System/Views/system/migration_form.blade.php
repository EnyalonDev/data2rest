@extends('layouts.admin')

@section('title', $lang['migration']['title'])

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold text-slate-100 mb-6">{{ $lang['migration']['title'] }}</h1>

            <div class="bg-slate-800 rounded-xl shadow-lg border border-slate-700 p-6">
                <!-- Warning / Info Box -->
                <div class="mb-6 flex items-start gap-4 p-4 bg-blue-900/20 border border-blue-800/50 rounded-lg">
                    <div class="text-blue-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-blue-200">{{ $lang['migration']['warning_title'] }}</h3>
                        <p class="text-sm text-blue-300 mt-1">
                            {{ $lang['migration']['warning_desc'] }}
                        </p>
                        <p class="text-sm text-blue-300 mt-2 font-semibold">
                            {{ $lang['migration']['warning_note'] }}
                        </p>
                    </div>
                </div>

                <form id="migration-form" onsubmit="startMigration(event)" class="space-y-6">
                    <!-- DB Type Selection -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="cursor-pointer relative">
                            <input type="radio" name="type" value="mysql" class="peer sr-only" checked
                                onchange="toggleFields()">
                            <div
                                class="p-4 rounded-lg border-2 border-slate-700 bg-slate-800/50 peer-checked:border-indigo-500 peer-checked:bg-indigo-500/10 transition-all text-center hover:bg-slate-700">
                                <span class="text-3xl block mb-2">🐬</span>
                                <span class="font-medium text-white">{{ $lang['install']['mysql_title'] }}</span>
                            </div>
                        </label>
                        <label class="cursor-pointer relative">
                            <input type="radio" name="type" value="pgsql" class="peer sr-only" onchange="toggleFields()">
                            <div
                                class="p-4 rounded-lg border-2 border-slate-700 bg-slate-800/50 peer-checked:border-indigo-500 peer-checked:bg-indigo-500/10 transition-all text-center hover:bg-slate-700">
                                <span class="text-3xl block mb-2">🐘</span>
                                <span class="font-medium text-white">{{ $lang['install']['pgsql_title'] }}</span>
                            </div>
                        </label>
                    </div>

                    <!-- Fields -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs font-medium text-slate-400 mb-1">{{ $lang['install']['db_host'] }}</label>
                            <input type="text" name="host" value="localhost"
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:ring-1 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label
                                class="block text-xs font-medium text-slate-400 mb-1">{{ $lang['install']['db_port'] }}</label>
                            <input type="text" name="port" id="input-port" value="3306"
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:ring-1 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">{{ $lang['install']['db_name'] }}
                            (Destino)</label>
                        <input type="text" name="database" value="data2rest_system"
                            class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:ring-1 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs font-medium text-slate-400 mb-1">{{ $lang['install']['db_user'] }}</label>
                            <input type="text" name="username"
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:ring-1 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label
                                class="block text-xs font-medium text-slate-400 mb-1">{{ $lang['install']['db_password'] }}</label>
                            <input type="password" name="password"
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:ring-1 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <div id="error-msg" class="hidden p-3 rounded bg-red-900/20 border border-red-800 text-red-300 text-sm">
                    </div>

                    <div class="pt-4 border-t border-slate-700">
                        <button type="submit" id="btn-submit"
                            class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-lg shadow-indigo-500/30 transition-all flex justify-center items-center">
                            <span>{{ $lang['migration']['start_btn'] }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Inject JS translations
        window.I18N = {
            confirm: "{{ $lang['migration']['confirm_msg'] }}",
            migrating: "{{ $lang['migration']['migrating'] }}",
            success: "{{ $lang['migration']['success_msg'] }}",
            retry: "{{ $lang['migration']['retry_btn'] }}"
        };

        function toggleFields() {
            const type = document.querySelector('input[name="type"]:checked').value;
            document.getElementById('input-port').value = (type === 'mysql') ? '3306' : '5432';
        }

        async function startMigration(e) {
            e.preventDefault();
            if (!confirm(window.I18N.confirm)) return;

            const form = e.target;
            const btn = document.getElementById('btn-submit');
            const errorDiv = document.getElementById('error-msg');

            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> ' + window.I18N.migrating;
            errorDiv.classList.add('hidden');

            try {
                const formData = new FormData(form);
                const response = await fetch('{{ $baseUrl }}admin/system/migrate/run', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(window.I18N.success);
                    window.location.href = result.redirect;
                } else {
                    throw new Error(result.message || 'Error desconocido');
                }
            } catch (err) {
                errorDiv.textContent = 'Error: ' + err.message;
                errorDiv.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = window.I18N.retry;
            }
        }
    </script>
@endsection