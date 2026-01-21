@extends('layouts.main')

@section('title', $lang['databases']['new_node'])

@section('content')
    <header class="mb-12">
        <h1 class="text-5xl font-black text-p-title italic tracking-tighter uppercase">
            {{ $lang['databases']['new_node'] }}
        </h1>
        <p class="text-p-muted font-medium tracking-tight">
            {{ $lang['databases']['create_subtitle'] ?? 'Setup a new database connection for your project' }}
        </p>
    </header>

    <div class="max-w-4xl mx-auto">
        <form id="createDbForm" method="POST" action="{{ $baseUrl }}admin/databases/create-multi"
            class="glass-card space-y-8">
            {!! $csrf_field ?? '' !!}
            <input type="hidden" name="type" id="dbType" value="sqlite">

            <!-- Type Selector -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="db-type-card active cursor-pointer border-2 border-primary/20 hover:border-primary bg-white/5 p-6 rounded-xl transition-all duration-300 relative group overflow-hidden"
                    data-type="sqlite">
                    <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative z-10 flex flex-col items-center text-center gap-4">
                        <div class="w-16 h-16 bg-slate-500/10 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-p-title uppercase italic">
                                {{ $lang['install']['sqlite_title'] }}
                            </h3>
                            <p class="text-sm text-p-muted mt-1">{{ $lang['install']['sqlite_desc'] }}</p>
                        </div>
                        <div class="check-icon hidden absolute top-4 right-4 text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="db-type-card cursor-pointer border-2 border-white/10 hover:border-primary bg-white/5 p-6 rounded-xl transition-all duration-300 relative group overflow-hidden"
                    data-type="mysql">
                    <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative z-10 flex flex-col items-center text-center gap-4">
                        <div class="w-16 h-16 bg-orange-500/10 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-p-title uppercase italic">
                                {{ $lang['install']['mysql_title'] }}
                            </h3>
                            <p class="text-sm text-p-muted mt-1">{{ $lang['install']['mysql_desc'] }}</p>
                        </div>
                        <div class="check-icon hidden absolute top-4 right-4 text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="db-type-card cursor-pointer border-2 border-white/10 hover:border-primary bg-white/5 p-6 rounded-xl transition-all duration-300 relative group overflow-hidden"
                    data-type="pgsql">
                    <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative z-10 flex flex-col items-center text-center gap-4">
                        <div class="w-16 h-16 bg-blue-500/10 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-p-title uppercase italic">
                                {{ $lang['install']['pgsql_title'] }}
                            </h3>
                            <p class="text-sm text-p-muted mt-1">{{ $lang['install']['pgsql_desc'] }}</p>
                        </div>
                        <div class="check-icon hidden absolute top-4 right-4 text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Import / Upload -->
                <div class="db-type-card cursor-pointer border-2 border-white/10 hover:border-primary bg-white/5 p-6 rounded-xl transition-all duration-300 relative group overflow-hidden"
                    data-type="import">
                    <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative z-10 flex flex-col items-center text-center gap-4">
                        <div class="w-16 h-16 bg-purple-500/10 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-p-title uppercase italic">
                                {{ $lang['databases']['import_sql_title'] ?? 'Import SQL' }}
                            </h3>
                            <p class="text-sm text-p-muted mt-1">
                                {{ $lang['databases']['import_help'] ?? 'Upload a .sql file to create a new database' }}
                            </p>
                        </div>
                        <div class="check-icon hidden absolute top-4 right-4 text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Common Fields -->
            <div class="space-y-4">
                <div class="flex flex-col gap-2">
                    <label
                        class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">{{ $lang['databases']['node_name'] ?? 'Connection Name' }}
                        *</label>
                    <input type="text" id="name" name="name" required
                        placeholder="{{ $lang['databases']['node_placeholder'] }}"
                        class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary transition-colors">
                </div>
            </div>

            <!-- SQLite Config -->
            <div id="sqliteConfig" class="config-section p-4 bg-blue-500/10 rounded-lg border border-blue-500/20">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h4 class="font-bold text-blue-100">{{ $lang['databases']['sqlite_db_title'] ?? 'SQLite Database' }}
                        </h4>
                        <p class="text-sm text-blue-200 mt-1">
                            {{ $lang['databases']['sqlite_auto_msg'] ?? 'The database file will be created automatically in the data directory. No additional configuration needed.' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- MySQL Config -->
            <div id="mysqlConfig" class="config-section hidden space-y-6">
                <div class="border-t border-white/10 pt-6">
                    <h3 class="text-lg font-bold text-p-title uppercase italic mb-6">
                        {{ $lang['databases']['mysql_settings'] ?? 'MySQL Connection Settings' }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">{{ $lang['install']['db_host'] }}
                                *</label>
                            <input type="text" id="mysql_host" name="mysql_host" value="localhost" placeholder="localhost"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">{{ $lang['install']['db_port'] }}</label>
                            <input type="number" id="mysql_port" name="mysql_port" value="3306" placeholder="3306"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">{{ $lang['install']['db_name'] }}
                                *</label>
                            <input type="text" id="mysql_database" name="mysql_database" placeholder="my_database"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">{{ $lang['install']['db_charset'] }}</label>
                            <input type="text" id="mysql_charset" name="mysql_charset" value="utf8mb4" placeholder="utf8mb4"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">{{ $lang['install']['db_user'] }}
                                *</label>
                            <input type="text" id="mysql_username" name="mysql_username" value="root" placeholder="root"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">{{ $lang['install']['db_password'] }}</label>
                            <input type="password" id="mysql_password" name="mysql_password" placeholder="••••••••"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button type="button" id="testConnectionBtn"
                        class="btn-secondary px-6 py-2 rounded-lg font-bold text-sm uppercase tracking-wider hover:bg-white/10 transition-colors">
                        {{ $lang['databases']['test_connection'] ?? 'Test Connection' }}
                    </button>
                    <div id="testResult" class="hidden px-4 py-2 rounded-lg text-sm font-medium"></div>
                    <p class="text-xs text-amber-400/80 mt-2 font-medium">
                        <span class="font-bold">{{ $lang['databases']['note'] ?? 'Note' }}:</span>
                        {{ $lang['databases']['test_fail_note'] ?? 'If the database does not exist, the Connection Test will fail. You can still proceed to "Create Database" and the system will attempt to create it automatically.' }}
                    </p>
                </div>
            </div>

            <!-- PostgreSQL Config -->
            <div id="pgsqlConfig" class="config-section hidden space-y-6">
                <div class="border-t border-white/10 pt-6">
                    <h3 class="text-lg font-bold text-p-title uppercase italic mb-6">
                        {{ $lang['databases']['pgsql_settings'] ?? 'PostgreSQL Connection Settings' }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">{{ $lang['install']['db_host'] }}
                                *</label>
                            <input type="text" id="pgsql_host" name="pgsql_host" value="localhost" placeholder="localhost"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">{{ $lang['install']['db_port'] }}</label>
                            <input type="number" id="pgsql_port" name="pgsql_port" value="5432" placeholder="5432"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">{{ $lang['install']['db_name'] }}
                                *</label>
                            <input type="text" id="pgsql_database" name="pgsql_database" placeholder="my_database"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">{{ $lang['install']['db_schema'] }}</label>
                            <input type="text" id="pgsql_schema" name="pgsql_schema" value="public" placeholder="public"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">{{ $lang['install']['db_user'] }}
                                *</label>
                            <input type="text" id="pgsql_username" name="pgsql_username" value="postgres"
                                placeholder="postgres"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">{{ $lang['install']['db_password'] }}</label>
                            <input type="password" id="pgsql_password" name="pgsql_password" placeholder="••••••••"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button type="button" id="testConnectionBtnPg"
                        class="btn-secondary px-6 py-2 rounded-lg font-bold text-sm uppercase tracking-wider hover:bg-white/10 transition-colors">
                        {{ $lang['databases']['test_connection'] ?? 'Test Connection' }}
                    </button>
                    <div id="testResultPg" class="hidden px-4 py-2 rounded-lg text-sm font-medium"></div>
                    <p class="text-xs text-amber-400/80 mt-2 font-medium">
                        <span class="font-bold">{{ $lang['databases']['note'] ?? 'Note' }}:</span>
                        {{ $lang['databases']['test_fail_note'] ?? 'If the database does not exist, the Connection Test will fail. You can still proceed to "Create Database" and the system will attempt to create it automatically.' }}
                    </p>
                </div>
            </div>

            <!-- Import Config -->
            <div id="importConfig" class="config-section hidden space-y-6">
                <div class="border-t border-white/10 pt-6">
                    <h3 class="text-lg font-bold text-p-title uppercase italic mb-6">
                        {{ $lang['databases']['import_settings'] ?? 'Import Settings' }}
                    </h3>

                    <div class="space-y-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">
                                {{ $lang['databases']['import_file'] ?? 'SQL File' }} *
                            </label>
                            <input type="file" id="sql_file" name="sql_file" accept=".sql"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title text-xs file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                            <p class="text-[10px] text-p-muted italic opacity-70">
                                {{ $lang['databases']['import_help'] ?? 'Select a valid .sql file to create a new database from.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-4 pt-6 border-t border-white/10">
                <button type="submit"
                    class="btn-primary px-8 py-3 rounded-xl font-black uppercase tracking-widest text-sm shadow-lg shadow-primary/20 hover:shadow-primary/40 transition-all">
                    {{ $lang['databases']['create_node'] }}
                </button>
                <a href="{{ $baseUrl }}admin/databases"
                    class="text-p-muted font-bold text-xs uppercase tracking-widest hover:text-white transition-colors">
                    {{ $lang['common']['cancel'] }}
                </a>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Type Selector Logic
            const cards = document.querySelectorAll('.db-type-card');
            const dbTypeInput = document.getElementById('dbType');
            const sqliteConfig = document.getElementById('sqliteConfig');
            const mysqlConfig = document.getElementById('mysqlConfig');
            const pgsqlConfig = document.getElementById('pgsqlConfig');
            const importConfig = document.getElementById('importConfig');
            const form = document.getElementById('createDbForm');
            const baseAction = '{{ $baseUrl }}admin/databases/create-multi';
            const importAction = '{{ $baseUrl }}admin/databases/import';
            const submitBtn = form.querySelector('button[type="submit"]');
            const defaultSubmitText = submitBtn.innerText;

            cards.forEach(card => {
                card.addEventListener('click', () => {
                    // Update UI
                    cards.forEach(c => {
                        c.classList.remove('border-primary', 'active');
                        c.classList.add('border-white/10');
                        c.querySelector('.check-icon').classList.add('hidden');
                    });

                    card.classList.remove('border-white/10');
                    card.classList.add('border-primary', 'active');
                    card.querySelector('.check-icon').classList.remove('hidden');

                    // Update Logic
                    const type = card.dataset.type;
                    dbTypeInput.value = type;

                    // Hide all configs
                    sqliteConfig.classList.add('hidden');
                    mysqlConfig.classList.add('hidden');
                    pgsqlConfig.classList.add('hidden');
                    if (importConfig) importConfig.classList.add('hidden');

                    // Reset Form Attributes
                    form.action = baseAction;
                    form.enctype = 'application/x-www-form-urlencoded';
                    submitBtn.innerText = defaultSubmitText;

                    // Show selected config
                    if (type === 'sqlite') {
                        sqliteConfig.classList.remove('hidden');
                    } else if (type === 'mysql') {
                        mysqlConfig.classList.remove('hidden');
                        document.getElementById('mysql_host').value = 'localhost';
                    } else if (type === 'pgsql') {
                        pgsqlConfig.classList.remove('hidden');
                        document.getElementById('pgsql_host').value = '/tmp';
                    } else if (type === 'import') {
                        if (importConfig) importConfig.classList.remove('hidden');
                        form.action = importAction;
                        form.enctype = 'multipart/form-data';
                        submitBtn.innerText = '{{ $lang['databases']['import_btn'] ?? 'Import Database' }}';
                    }
                });
            });

            // Test Connection Logic
            const testBtn = document.getElementById('testConnectionBtn');
            const testResult = document.getElementById('testResult');

            if (testBtn) {
                testBtn.addEventListener('click', async () => {
                    testBtn.disabled = true;
                    testBtn.innerHTML = '<span class="animate-spin inline-block mr-2">⟳</span> {{ $lang['install']['testing'] ?? 'Testing...' }}';
                    testResult.className = 'hidden';

                    // Get CSRF token from the form
                    const csrfToken = document.querySelector('input[name="_token"]')?.value || '';

                    const formData = new URLSearchParams({
                        type: 'mysql',
                        host: document.getElementById('mysql_host').value,
                        port: document.getElementById('mysql_port').value,
                        database: document.getElementById('mysql_database').value,
                        username: document.getElementById('mysql_username').value,
                        password: document.getElementById('mysql_password').value,
                        charset: document.getElementById('mysql_charset').value,
                        _token: csrfToken
                    });

                    try {
                        const response = await fetch('{{ $baseUrl }}admin/databases/test-connection', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: formData
                        });

                        const result = await response.json();

                        testResult.classList.remove('hidden');
                        if (result.success) {
                            testResult.className = 'bg-green-500/10 text-green-400 border border-green-500/20 px-4 py-2 rounded-lg text-sm font-medium block';
                            testResult.innerHTML = '✓ ' + result.message;
                        } else {
                            testResult.className = 'bg-red-500/10 text-red-400 border border-red-500/20 px-4 py-2 rounded-lg text-sm font-medium block';
                            testResult.innerHTML = '✗ ' + result.message;
                        }
                    } catch (e) {
                        testResult.className = 'bg-red-500/10 text-red-400 border border-red-500/20 px-4 py-2 rounded-lg text-sm font-medium block';
                        testResult.innerHTML = '✗ {{ $lang['common']['network_failure'] ?? 'Error connecting to server' }}';
                    } finally {
                        testBtn.disabled = false;
                        testBtn.innerHTML = '{{ $lang['databases']['test_connection'] ?? 'Test Connection' }}';
                    }
                });
            }

            // Test Connection Logic for PostgreSQL
            const testBtnPg = document.getElementById('testConnectionBtnPg');
            const testResultPg = document.getElementById('testResultPg');

            if (testBtnPg) {
                testBtnPg.addEventListener('click', async () => {
                    testBtnPg.disabled = true;
                    testBtnPg.innerHTML = '<span class="animate-spin inline-block mr-2">⟳</span> {{ $lang['install']['testing'] ?? 'Testing...' }}';
                    testResultPg.className = 'hidden';

                    // Get CSRF token from the form
                    const csrfToken = document.querySelector('input[name="_token"]')?.value || '';

                    const formData = new URLSearchParams({
                        type: 'pgsql',
                        host: document.getElementById('pgsql_host').value,
                        port: document.getElementById('pgsql_port').value,
                        database: document.getElementById('pgsql_database').value,
                        username: document.getElementById('pgsql_username').value,
                        password: document.getElementById('pgsql_password').value,
                        schema: document.getElementById('pgsql_schema').value,
                        _token: csrfToken
                    });

                    try {
                        const response = await fetch('{{ $baseUrl }}admin/databases/test-connection', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: formData
                        });

                        const result = await response.json();

                        testResultPg.classList.remove('hidden');
                        if (result.success) {
                            testResultPg.className = 'bg-green-500/10 text-green-400 border border-green-500/20 px-4 py-2 rounded-lg text-sm font-medium block';
                            testResultPg.innerHTML = '✓ ' + result.message;
                        } else {
                            testResultPg.className = 'bg-red-500/10 text-red-400 border border-red-500/20 px-4 py-2 rounded-lg text-sm font-medium block';
                            testResultPg.innerHTML = '✗ ' + result.message;
                        }
                    } catch (e) {
                        testResultPg.className = 'bg-red-500/10 text-red-400 border border-red-500/20 px-4 py-2 rounded-lg text-sm font-medium block';
                        testResultPg.innerHTML = '✗ {{ $lang['common']['network_failure'] ?? 'Error connecting to server' }}';
                    } finally {
                        testBtnPg.disabled = false;
                        testBtnPg.innerHTML = '{{ $lang['databases']['test_connection'] ?? 'Test Connection' }}';
                    }
                });
            }

            // Auto-fill database name from display name
            const nameInput = document.getElementById('name');
            const mysqlDbInput = document.getElementById('mysql_database');
            const pgsqlDbInput = document.getElementById('pgsql_database');

            nameInput.addEventListener('input', (e) => {
                // Create a basic slug for database names (lowercase, underscores)
                const val = e.target.value;
                const slug = val.toLowerCase().replace(/[^a-z0-9_]+/g, '_').replace(/^_+|_+$/g, '');

                // Update only if user hasn't manually edited (simplified: just update)
                // For a creation form, usually aggressive syncing is preferred until they manually change the target field.
                // To be safe, we'll just update them. The user can edit them afterwards.
                if (mysqlDbInput.value === '' || mysqlDbInput.value === mysqlDbInput.getAttribute('data-prev-sync')) {
                    mysqlDbInput.value = slug;
                    mysqlDbInput.setAttribute('data-prev-sync', slug);
                }

                if (pgsqlDbInput.value === '' || pgsqlDbInput.value === pgsqlDbInput.getAttribute('data-prev-sync')) {
                    pgsqlDbInput.value = slug;
                    pgsqlDbInput.setAttribute('data-prev-sync', slug);
                }
            });

            // Track manual edits to stop syncing
            [mysqlDbInput, pgsqlDbInput].forEach(input => {
                input.addEventListener('input', (e) => {
                    input.setAttribute('data-manual', 'true');
                    // Remove the sync attribute so we don't overwrite manual changes
                    input.removeAttribute('data-prev-sync');
                });
            });

            // Form Validation
            document.getElementById('createDbForm').addEventListener('submit', (e) => {
                const type = dbTypeInput.value;
                if (type === 'mysql') {
                    const db = document.getElementById('mysql_database').value;
                    const user = document.getElementById('mysql_username').value;
                    if (!db || !user) {
                        e.preventDefault();
                        alert('{{ $lang['databases']['fill_required_msg'] ?? 'Please fill in database name and username' }}');
                    }
                } else if (type === 'pgsql') {
                    const db = document.getElementById('pgsql_database').value;
                    const user = document.getElementById('pgsql_username').value;
                    if (!db || !user) {
                        e.preventDefault();
                        alert('{{ $lang['databases']['fill_required_msg'] ?? 'Please fill in database name and username' }}');
                    }
                } else if (type === 'import') {
                    const file = document.getElementById('sql_file').files[0];
                    if (!file) {
                        e.preventDefault();
                        alert('{{ $lang['databases']['bg_file_required'] ?? 'Please select a SQL file' }}');
                    }
                }
            });
        });
    </script>
@endsection