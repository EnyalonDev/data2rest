@extends('layouts.main')

@section('title', 'Create Database')

@section('content')
    <header class="mb-12">
        <h1 class="text-5xl font-black text-p-title italic tracking-tighter uppercase">
            Create Database
        </h1>
        <p class="text-p-muted font-medium tracking-tight">Setup a new database connection for your project</p>
    </header>

    <div class="max-w-4xl mx-auto">
        <form id="createDbForm" method="POST" action="{{ $baseUrl }}admin/databases/create-multi"
            class="glass-card space-y-8">
            {!! $csrf_field ?? '' !!}
            <input type="hidden" name="type" id="dbType" value="sqlite">

            <!-- Type Selector -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                            <h3 class="text-xl font-bold text-p-title uppercase italic">SQLite</h3>
                            <p class="text-sm text-p-muted mt-1">File-based database.<br>Perfect for development.</p>
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
                            <h3 class="text-xl font-bold text-p-title uppercase italic">MySQL</h3>
                            <p class="text-sm text-p-muted mt-1">Client-server database.<br>Ideal for production.</p>
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
                            <h3 class="text-xl font-bold text-p-title uppercase italic">PostgreSQL</h3>
                            <p class="text-sm text-p-muted mt-1">Advanced SQL database.<br>Enterprise-grade.</p>
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
                    <label class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Database Name
                        *</label>
                    <input type="text" id="name" name="name" required placeholder="My Project Database"
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
                        <h4 class="font-bold text-blue-100">SQLite Database</h4>
                        <p class="text-sm text-blue-200 mt-1">The database file will be created automatically in the data
                            directory. No additional configuration needed.</p>
                    </div>
                </div>
            </div>

            <!-- MySQL Config -->
            <div id="mysqlConfig" class="config-section hidden space-y-6">
                <div class="border-t border-white/10 pt-6">
                    <h3 class="text-lg font-bold text-p-title uppercase italic mb-6">MySQL Connection Settings</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Host *</label>
                            <input type="text" id="mysql_host" name="mysql_host" value="localhost" placeholder="localhost"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Port</label>
                            <input type="number" id="mysql_port" name="mysql_port" value="3306" placeholder="3306"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Database Name
                                *</label>
                            <input type="text" id="mysql_database" name="mysql_database" placeholder="my_database"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Character
                                Set</label>
                            <input type="text" id="mysql_charset" name="mysql_charset" value="utf8mb4" placeholder="utf8mb4"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Username
                                *</label>
                            <input type="text" id="mysql_username" name="mysql_username" value="root" placeholder="root"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Password</label>
                            <input type="password" id="mysql_password" name="mysql_password" placeholder="••••••••"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button type="button" id="testConnectionBtn"
                        class="btn-secondary px-6 py-2 rounded-lg font-bold text-sm uppercase tracking-wider hover:bg-white/10 transition-colors">
                        Test Connection
                    </button>
                    <div id="testResult" class="hidden px-4 py-2 rounded-lg text-sm font-medium"></div>
                    <p class="text-xs text-amber-400/80 mt-2 font-medium">
                        <span class="font-bold">Note:</span> If the database does not exist, the Connection Test will fail. 
                        You can still proceed to "Create Database" and the system will attempt to create it automatically.
                    </p>
                </div>
            </div>

            <!-- PostgreSQL Config -->
            <div id="pgsqlConfig" class="config-section hidden space-y-6">
                <div class="border-t border-white/10 pt-6">
                    <h3 class="text-lg font-bold text-p-title uppercase italic mb-6">PostgreSQL Connection Settings</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Host *</label>
                            <input type="text" id="pgsql_host" name="pgsql_host" value="localhost" placeholder="localhost"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Port</label>
                            <input type="number" id="pgsql_port" name="pgsql_port" value="5432" placeholder="5432"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Database Name
                                *</label>
                            <input type="text" id="pgsql_database" name="pgsql_database" placeholder="my_database"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Schema</label>
                            <input type="text" id="pgsql_schema" name="pgsql_schema" value="public" placeholder="public"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Username
                                *</label>
                            <input type="text" id="pgsql_username" name="pgsql_username" value="postgres"
                                placeholder="postgres"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label
                                class="text-[10px] font-black text-p-muted uppercase tracking-widest ml-1">Password</label>
                            <input type="password" id="pgsql_password" name="pgsql_password" placeholder="••••••••"
                                class="form-input w-full bg-white/5 border-white/10 text-p-title focus:border-primary">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button type="button" id="testConnectionBtnPg"
                        class="btn-secondary px-6 py-2 rounded-lg font-bold text-sm uppercase tracking-wider hover:bg-white/10 transition-colors">
                        Test Connection
                    </button>
                    <div id="testResultPg" class="hidden px-4 py-2 rounded-lg text-sm font-medium"></div>
                    <p class="text-xs text-amber-400/80 mt-2 font-medium">
                        <span class="font-bold">Note:</span> If the database does not exist, the Connection Test will fail. 
                        You can still proceed to "Create Database" and the system will attempt to create it automatically.
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-4 pt-6 border-t border-white/10">
                <button type="submit"
                    class="btn-primary px-8 py-3 rounded-xl font-black uppercase tracking-widest text-sm shadow-lg shadow-primary/20 hover:shadow-primary/40 transition-all">
                    Create Database
                </button>
                <a href="{{ $baseUrl }}admin/databases"
                    class="text-p-muted font-bold text-xs uppercase tracking-widest hover:text-white transition-colors">
                    Cancel
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

                    // Show selected config
                    if (type === 'sqlite') {
                        sqliteConfig.classList.remove('hidden');
                    } else if (type === 'mysql') {
                        mysqlConfig.classList.remove('hidden');
                    } else if (type === 'pgsql') {
                        pgsqlConfig.classList.remove('hidden');
                    }
                });
            });

            // Test Connection Logic
            const testBtn = document.getElementById('testConnectionBtn');
            const testResult = document.getElementById('testResult');

            testBtn.addEventListener('click', async () => {
                testBtn.disabled = true;
                testBtn.innerHTML = '<span class="animate-spin inline-block mr-2">⟳</span> Testing...';
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
                    testResult.innerHTML = '✗ Error connecting to server';
                } finally {
                    testBtn.disabled = false;
                    testBtn.innerHTML = 'Test Connection';
                }
            });

            // Test Connection Logic for PostgreSQL
            const testBtnPg = document.getElementById('testConnectionBtnPg');
            const testResultPg = document.getElementById('testResultPg');

            testBtnPg.addEventListener('click', async () => {
                testBtnPg.disabled = true;
                testBtnPg.innerHTML = '<span class="animate-spin inline-block mr-2">⟳</span> Testing...';
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
                    testResultPg.innerHTML = '✗ Error connecting to server';
                } finally {
                    testBtnPg.disabled = false;
                    testBtnPg.innerHTML = 'Test Connection';
                }
            });

            // Form Validation
            document.getElementById('createDbForm').addEventListener('submit', (e) => {
                const type = dbTypeInput.value;
                if (type === 'mysql') {
                    const db = document.getElementById('mysql_database').value;
                    const user = document.getElementById('mysql_username').value;
                    if (!db || !user) {
                        e.preventDefault();
                        alert('Please fill in database name and username');
                    }
                } else if (type === 'pgsql') {
                    const db = document.getElementById('pgsql_database').value;
                    const user = document.getElementById('pgsql_username').value;
                    if (!db || !user) {
                        e.preventDefault();
                        alert('Please fill in database name and username');
                    }
                }
            });
        });
    </script>
@endsection